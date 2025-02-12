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

        <button :disabled="!file" type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
          Upload CSV
        </button>
      </form>

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

          this.message = response.data.statuss || "File uploaded successfully!";
          this.error = "";
        } catch (err) {
          this.error = "Failed to upload. " + (err.response?.data?.message || err.message);
          this.message = "";
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
