module.exports = {
  '/api': {
    target: 'http://127.0.0.1:8080',
    secure: false,
    changeOrigin: false,
    configure: (proxy) => {
      proxy.on('proxyReq', (proxyReq) => {
        // Force l'en-tête Host du tenant : stancl identifie le cabinet par le
        // domaine, sans dépendre du fichier hosts Windows ni du DNS.
        proxyReq.setHeader('host', 'magis-plus-center.localhost');
      });
    },
  },
};