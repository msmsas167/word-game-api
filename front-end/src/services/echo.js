import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import apiClient from './apiClient.js'; // <-- The corrected, included import statement

// Make Pusher available globally for Echo
window.Pusher = Pusher;

// Create and configure the Echo instance
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    // Configure the authorizer to use our API token
    authorizer: (channel, options) => {
        const authToken = localStorage.getItem('authToken'); // Get the latest token
        return {
            authorize: (socketId, callback) => {
                apiClient.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                }, {
                    headers: {
                        Authorization: `Bearer ${authToken}`
                    }
                })
                .then(response => {
                    callback(false, response.data);
                })
                .catch(error => {
                    callback(true, error);
                });
            },
        };
    },
});

export default echo;
