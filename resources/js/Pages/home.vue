<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DropdownLink from '@/Components/DropdownLink.vue';
</script>

<template>
    <Head title="Home" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/logo.png">
<header class="navbar bg-base-100">
  <div class="navbar-start">
    <a href="/">
  <button class="btn btn-square btn-ghost">
    <img src="/images/logo.png" alt="Logo" class="h-10">
  </button>
</a>
  </div>
  <div class="navbar-center">
    <div class="navbar bg-base-100"><a href="/bus" class="btn btn-ghost text-xl">Buss</a></div>
    <div class="navbar bg-base-100"><a href="/trolleybus" class="btn btn-ghost text-xl">Trolleybuss</a></div>
    <div class="navbar bg-base-100"><a href="/tram" class="btn btn-ghost text-xl">Tram</a></div>
    <div class="navbar bg-base-100"><a href="/train" class="btn btn-ghost text-xl">Train</a></div>
  </div>
  <div class="navbar-end">
  <div class="dropdown dropdown-end">
    <button tabindex="0" role="button" class="btn btn-square btn-ghost">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        class="inline-block h-5 w-5 stroke-current">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
        </path>
      </svg>
    </button>
  <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow">
    <li v-if="!$page.props.auth.user">
      <a :href="route('login')">Login</a>
    </li>
    <li><a>Settings</a></li>
    <div v-if="$page.props.auth.user">
        <li>
            <Link :href="route('logout')" method="post">Log Out</Link>
        </li>
    </div>
  </ul>
  </div>
</div>

</header>

<div class="middle" style="display: flex; flex-direction: column; align-items: center; padding-top: 20px; gap: 20px;">
    <h1 style="font-size: 2em; font-weight: bold;">Publisko transportu saraksti</h1>

    <input type="text" placeholder="From" class="input input-ghost w-full max-w-xs" style="border-bottom: 2px solid black;" />
    <input type="text" placeholder="To" class="input input-ghost w-full max-w-xs" style="border-bottom: 2px solid black;" />
</div>



</template>
