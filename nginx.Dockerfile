FROM nginx:alpine

# Remove default Nginx configuration
RUN rm /etc/nginx/conf.d/default.conf

# Copy custom Nginx configuration
COPY nginx.conf /etc/nginx/conf.d/

# Create directory for Laravel public files
RUN mkdir -p /var/www/html/public

# Set permissions for public directory only
RUN chown -R nginx:nginx /var/www/html/public

# Expose port 80
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"] 