<template>
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-4">CSV Import</h1>

      <form @submit.prevent="uploadFile" class="space-y-4">
        <div>
          <label for="import_type" class="block mb-2">Select Data Type</label>
          <select v-model="importType" class="border p-2 rounded w-full">
            <option value="shapes">Shapes</option>
            <option value="calendar">Calendar</option>
            <option value="stops">Stops</option>
            <option value="routes">Routes</option>
            <option value="calendar_dates">Calendar Dates</option>
            <option value="trips">Trips</option>
            <option value="stop_times">Stop Times</option>
          </select>
        </div>

        <div>
          <label for="file" class="block mb-2">Upload CSV File</label>
          <input
            type="file"
            @change="handleFileChange"
            accept=".csv"
            class="border p-2 rounded w-full"
          />
        </div>

        <button
          :disabled="!file || loading"
          type="submit"
          class="bg-blue-500 text-white px-4 py-2 rounded"
        >
          {{ loading ? "Uploading..." : "Upload CSV" }}
        </button>
      </form>

      <!-- Loading Spinner -->
      <div v-if="loading" class="mt-4 flex items-center space-x-2">
        <svg
          class="animate-spin h-5 w-5 text-blue-500"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <span>Uploading...</span>
      </div>

      <div v-if="message" class="mt-4 text-green-600">{{ message }}</div>
      <div v-if="error" class="mt-4 text-red-600">{{ error }}</div>
    </div>
  </template>

  <script>
  import axios from "axios";

  export default {
    data() {
      return {
        file: null,
        message: "",
        error: "",
        importType: "shapes", // Default import type
        loading: false, // New loading state
      };
    },
    methods: {
      handleFileChange(event) {
        this.file = event.target.files[0];
      },
      async uploadFile() {
        if (!this.file) {
          this.error = "Please select a file.";
          return;
        }

        this.loading = true; // Start loading
        this.message = "";
        this.error = "";

        const formData = new FormData();
        formData.append("import_file", this.file);

        try {
          const response = await axios.post(
            `/import/${this.importType}`, // Using web.php route
            formData,
            {
              headers: {
                "Content-Type": "multipart/form-data",
              },
            }
          );

          this.message = response.data.status || "File uploaded successfully!";
          this.error = "";
        } catch (err) {
          this.error = "Failed to upload. " + (err.response?.data?.message || err.message);
          this.message = "";
        } finally {
          this.loading = false; // Stop loading
        }
      },
    },
  };
  </script>

  <style scoped>
  button:disabled {
    background-color: #ccc;
  }
  </style>
