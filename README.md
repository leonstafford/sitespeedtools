




### Local testing notes

 - Playwright end to end tests, including support for WP-CLI over SSH
 - requires hostname `wordpress` to resolve to 127.0.0.1/localhost
 - architectural decisions:
   - SSH from JS vs WP REST API 
   - sshd inside separate WP-CLI container, as it doesn't need to also run Apache
