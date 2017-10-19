FROM php:5.6.27-apache

#get libxml libs for php_soap.so
RUN apt-get update &&apt-get install -y libxml2-dev
#install php_soap
RUN docker-php-ext-install -j$(nproc) soap

#add workbench folder from this project into the container and copy into apache webroot
ADD workbench /workbench
RUN cp -R /workbench/* /var/www/html

#we use port 80 mostly
EXPOSE 80

#run apache
CMD ["apache2-foreground"]
