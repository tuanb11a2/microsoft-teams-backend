FROM node:latest

WORKDIR /var/www

COPY . .
RUN npm install -g laravel-echo-server

EXPOSE 6001

CMD ["laravel-echo-server", "start"]
