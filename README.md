# fb-pages-stats

## Create indice and put mapping on Elasticsearch

### Format
```bash
$ [command] <elasticsearch_server_ip> <indice_name_to_be_created>
```

```bash
$ bin/create 127.0.0.1 fb_stats 
$ bin/links 127.0.0.1 fb_stats 
```

## Get links and persist to Elasticsearch

```bash
$ bin/console links:get -v 
```
