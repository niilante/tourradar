# Tour-Radar DevOps Test

## Requirements:
- docker (at least 4GB of memory)
- docker-compose
- php 
- composer

*Due to an issue with `docker`, `docker-compose` might be very slow, disconnect the internet to speed up the creation of the containers. (https://github.com/docker/for-mac/issues/2260)

## Preparation

1. Run `composer install -d=./php` (to install Predis).
2. Run `docker-compose up`.

## Jenkins:

Jenkins dashboard is under [http://localhost:8082](http://localhost:8082).
The required credentials are:
```
user: tour-radar
password: 1234
```

## Kibana

Kibana dashboard is under [http://localhost:5601](http://localhost:5601).
The required credentials are:

## Application
To get the PHP application running will reachable under [http://localhost:8081](http://localhost:8081).

Actually,[http://localhost:8081](http://localhost:8081) is pointing to the `load-balancer`, but any request will go through
the Apache containers, and in case of requesting a `PHP` file, the request will reach the `PHP-fpm` container which is serving the proper `json`.

About Predis, there is another alternative to connect to `Redis` without any kind of library but the number of lines of code will be unnecessary for that test. 

## Redis Improvement

Especially for application who needs to handle many requests, the connection time to `redis` will, eventually, start to be a bottleneck for our platform.
One of the best solution it's to create a container which will keep the connection with `redis` open and, just spend the time to retrieve information.

The connection time in `docker-compose` between service is really fast, like `localhost` therefore, there is no cost for the application.
For another kind of orchestrators, using the `ambassador patter` will fake a local connection to keep it always updated.

This will help to spend just time retrieving the data from `redis` and saving the time.

FYI: [Redis proxy](https://github.com/twitter/twemproxy)

## Security Improvements
Since I missed a lot of security things I'd like to enumerate all of the missing parts as a nice to have in dev and MUST TO HAVE in prod.


### PHP

- **expose_php**: Turn it off to not show it in the header of the request.
- **post_max_size**: Almost always, this value is not enough to use in a real situation, increasing it will avoid having problems uploading files, for example.
- **allow_url_fopen**: Block any kind of "include" remote files.
- **display_errors**: In production, we must never show errors. Always log them but never show them.
- **log_errors**: That' the good option, should be turned on.

### Nginx and Apache

- **Always using SSL termination**: Will allow a server with an SSL connection to handle a huge amount of data using simultaneous connections. Also, it increases the server speed.

### Elasticsearch

- **Secure `elasticsearch` using `X-Pack`:** Included security provider to make `elasticsearch` secured using credentials.



## FAQ

**Why do I need to increase so much the memory of docker**
<br><br>
Usually, docker will exit showing `error status 137`.
That means that the memory assigned to containers is not enough and basically docker will send the `137 code` (`128` + `9` coming from `SIGKILL`).
The recommended memory is 4GB to not have any kind of issues.

**Why are you using always volumes instead of copying files inside the container?:**
<br><br>
As part of the docker approach, the image should be as light as possible, a generic. 
The usage of custom configuration should not affect the setup. 
That' why I tried to use always the official image.<br><br>
Of course, using the `COPY` directive will be enough to make the images running, but in case we will want to change the configuration, the MUST re-build the container again, using volumes, restarting will be enough. 
<br><br>Nevertheless, for pushing the images in production, we should care much more and have created specific images that will full fill the requirements.

**What about the job set up in Jenkins?**
<br><br>
Due to the timeline, the job was created manually using the Jenkins UI.
In other situations, the proper way to do it will be creating declarative pipelines using Jenkinsfile instead.
<br><br>
Will be much more clear for the co-workers and very descriptive.
In case of losing the data of the volume, will be very easy to recreate Jenkins with the proper jobs.


**Is there any other way to get the logs from the containers?**
<br><br>
In fact, I've counted 3 different ways to do it, I made the fastest one due to the low complexity of the problem.

1. **`rsyslog`:**
Very useful and easy to understand if you have the basics of Linux, but there's a huge downside, the installation.
To use `rsyslog` is needed to install it as an agent in the server side (`logstash` in this case)
and also in the clients, each of them. It's feasible but not recommended.

2. **`filebeat`:**
Amazing plugin (I really love that one) to retrieve the logs.
It' a really light service to get logs, send them to `elasticsearch` and use custom `kibana`dashboards.<br><br>
Also it' really nice to send the logs trough `udp` to `logstash`, where basically tails the logs folder content and start to send them to logstash.
The only problem is again, it's needed to install it in all the clients. But I'd not say that it's a problem
because its weight is really low. Very recommended.

3. **`Docker std-output`:**
Also highly recommended.
Basically for the `Apache` containers, like `Nginx` (in `Nginx` it's by default), you can send the logs
to the std-output as a json.<br><br>
`Logstash` will listen (using a volume) to the docker logs folder on the host machine for all the logs.
The difficulty of that approach is the path folder to the Docker logs folder in the host machine, that will change depending on OS.
The advantage of that method it's that you're able to run one `logstash` container in one node and get the logs from all the apps, therefore, any kind of dependency will be moved from the app itself to `logstash`.


**Why so many things in the `PHP` file?**
<br><br>
Yes true, but the answer it's again because of the time and the complexity.
I've not seen the reasons to create many classes to get the IP.
One important thing missing will be probably catching the exception such a `redis` connection or blocking any non `GET` requests.


**Why don't you expose all the ports in the `docker-compose` file?**
<br><br>
Well, basically because it's not needed, as a security measure, blocking any kind of possible intrusion from the host machine to the container is nice.
If something doesn't need to be accessible, should not be open.

