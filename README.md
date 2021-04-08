# 使用

```bash
php bin/hyperf.php encrypt:source [options]
```

options 说明:
- source [--s]: 要加密的目录, 多项使用逗号(,)分隔.
- destination [--d]: 加密后的文件存储目录.
- force [--f]: 当目标目录已经存在时，强制运行该操作.
- key_length [--k]: 参与加密的随机 key 长度.
