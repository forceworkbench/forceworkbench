FROM php:5.6.27-apache

RUN apt-get update

RUN apt-get install -y git libxml2-dev

RUN docker-php-ext-install -j$(nproc) soap

#RUN git clone https://github.com/ryanbrainard/forceworkbench.git /root/workbench

ADD workbench /workbench

RUN cp -R /workbench/* /var/www/html


# docker build -t sfwb ./

# docker run -ti -p 80:80 --entrypoint=/bin/bash sfwb

# docker run -t -p 80:80 sfwb


