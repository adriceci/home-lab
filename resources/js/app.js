import "./bootstrap";

import { createApp } from "vue";
import App from "@/App.vue";
import ApiService from "@/services/apiService";
import router from "@/router";

// Configure Audit Center
window.auditCenterConfig = {
    apiPrefix: 'audit-logs', // Without leading '/' so ApiService uses its baseURL '/api'
    apiService: ApiService,
};

const app = createApp(App);
app.use(router);
app.mount("#app");
