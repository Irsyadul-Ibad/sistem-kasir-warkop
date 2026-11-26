import axios from "axios";
import { useAuth } from "../store/auth";

export const api = axios.create({
  baseURL: "/api/v1",
  headers: { Accept: "application/json" },
});

api.interceptors.request.use((config) => {
  const token = useAuth.getState().token;
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) useAuth.getState().logout();
    return Promise.reject(err);
  }
);
