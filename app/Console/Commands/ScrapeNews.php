<?php

namespace App\Console\Commands;

use App\Models\News;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeNews extends Command
{
    protected $signature = 'news:scrape';
    protected $description = 'Scrape news from Rigas Satiksme website';

    public function handle()
    {
        $this->info('Starting news scraping...');
        $baseUrl = 'https://www.rigassatiksme.lv';
        $path = '/lv/aktuāla informācija';
        $newsItems = [];
        $page = 1;

        try {
            do {
                $url = $baseUrl . str_replace(' ', '%20', $path) . "/?page=" . $page;
                $this->info("Fetching page $page: $url");

                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->get($url);

                if (!$response->successful()) {
                    throw new \Exception("Failed to fetch page $page. Status code: " . $response->status());
                }

                $html = $response->body();
                $this->info("Got HTML response for page $page, length: " . strlen($html));

                $crawler = new Crawler($html);
                $items = $crawler->filter('ul.newsList li.item');
                $this->info("Found " . $items->count() . " news items on page $page");

                if ($items->count() === 0) {
                    $this->info("No more items found on page $page. Stopping.");
                    break;
                }

                $items->each(function (Crawler $node) use (&$newsItems) {
                    try {
                        // Extract date
                        $dateNode = $node->filter('span.date');
                        if ($dateNode->count() > 0) {
                            $dateText = trim($dateNode->text());
                            $this->info('Found date text: ' . $dateText);
                            $date = $this->parseLatvianDate($dateText);

                            // Extract title and URL
                            $titleNode = $node->filter('h3 a');
                            if ($titleNode->count() > 0) {
                                $title = trim($titleNode->text());
                                $url = $titleNode->attr('href');
                                if (!str_starts_with($url, 'http')) {
                                    $url = 'https://www.rigassatiksme.lv' . $url;
                                }

                                $this->info("Found news: $title");

                                // Get the full content by visiting the URL
                                $contentResponse = Http::withHeaders([
                                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                                ])->get($url);

                                $content = '';
                                if ($contentResponse->successful()) {
                                    $contentCrawler = new Crawler($contentResponse->body());
                                    $contentNode = $contentCrawler->filter('div.text');
                                    if ($contentNode->count() > 0) {
                                        $content = trim($contentNode->text());
                                    }
                                }

                                $newsItems[] = [
                                    'date' => $date,
                                    'title' => $title,
                                    'content' => $content,
                                    'source_url' => $url
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        $this->error('Error parsing news item: ' . $e->getMessage());
                    }
                });

                // Check if there are any items on the next page by making a test request
                $nextPage = $page + 1;
                $testUrl = $baseUrl . str_replace(' ', '%20', $path) . "/?page=" . $nextPage;
                $testResponse = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->get($testUrl);

                if (!$testResponse->successful() || !(new Crawler($testResponse->body()))->filter('ul.newsList li.item')->count()) {
                    $this->info("No more pages available after page $page. Stopping.");
                    break;
                }

                $page++;

                // Add a small delay between requests to be nice to the server
                sleep(1);

            } while (true);

            // Save to database
            foreach ($newsItems as $item) {
                News::updateOrCreate(
                    ['source_url' => $item['source_url']],
                    $item
                );
            }

            $this->info('Successfully scraped ' . count($newsItems) . ' news items from ' . ($page) . ' pages.');
        } catch (\Exception $e) {
            $this->error('Error scraping news: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    private function parseLatvianDate($dateText)
    {
        // Remove dots and extra whitespace
        $dateText = trim(str_replace('.', '', $dateText));

        // Latvian month abbreviations to numbers
        $monthMap = [
            'janv' => '01',
            'febr' => '02',
            'marts' => '03',
            'apr' => '04',
            'maijs' => '05',
            'jūn' => '06',
            'jūl' => '07',
            'aug' => '08',
            'sept' => '09',
            'okt' => '10',
            'nov' => '11',
            'dec' => '12',
            // Add variations that might appear
            'jan' => '01',
            'feb' => '02',
            'mar' => '03',
            'mai' => '05',
            'jun' => '06',
            'jul' => '07',
            'aug' => '08',
            'sep' => '09',
            'oct' => '10',
            'nov' => '11',
            'dec' => '12',
            // English month names that appear in the date
            'Jan' => '01',
            'Feb' => '02',
            'Mar' => '03',
            'Apr' => '04',
            'May' => '05',
            'Mai' => '05',
            'Jun' => '06',
            'Jul' => '07',
            'Aug' => '08',
            'Sep' => '09',
            'Oct' => '10',
            'Nov' => '11',
            'Dec' => '12'
        ];

        // Split the date text into parts
        $parts = explode(' ', $dateText);
        $this->info('Date parts: ' . json_encode($parts));

        if (count($parts) >= 3) {
            $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $monthKey = str_replace('.', '', $parts[1]);
            $month = $monthMap[trim($monthKey)] ?? '01';
            $year = $parts[2];

            $this->info("Parsed date components - Day: $day, Month: $month, Year: $year");
            return Carbon::createFromFormat('Y-m-d', "$year-$month-$day")->startOfDay();
        }

        $this->error('Could not parse date: ' . $dateText);
        return Carbon::now()->startOfDay();
    }
}
