## Testing

```bash
docker run -it --rm --name iutrace-dashboard -e PHP_EXTENSIONS="" -v "$PWD":/usr/src/app thecodingmachine/php:7.4-v4-cli bash
composer coverage
```