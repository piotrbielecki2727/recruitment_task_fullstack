import axios from 'axios';

const apiClient = axios.create({
    baseURL: '/api',
});

export const requestCancelTokenSource = () => axios.CancelToken.source();

export const getRates = (cancelToken) => apiClient.get('/rates', { cancelToken });

export const getRateHistory = (code, { date, days }, cancelToken) =>
    apiClient.get(`/rates/${code}/history`, { params: { date, days }, cancelToken });

