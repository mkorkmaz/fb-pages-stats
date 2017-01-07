# fb-pages-stats

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/0714cae47bcc42fda5b6abfeebc65e0a)](https://www.codacy.com/app/mehmet/fb-pages-stats?utm_source=github.com&utm_medium=referral&utm_content=mkorkmaz/fb-pages-stats&utm_campaign=badger)

## Create indice and put mapping on Elasticsearch

### Format
```bash
$ [command] <elasticsearch_server_ip> <indice_name_to_be_used>
```

```bash
$ bin/create 127.0.0.1 fb_pages 
$ bin/links 127.0.0.1 fb_pages 
```

## Get links and persist to Elasticsearch

```bash
$ bin/console links:get -v 
```

## Get stats and persist to Elasticsearch

```bash
$ bin/console stats:update_all -v 
```
