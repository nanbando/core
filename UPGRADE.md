UPGRADE before 1.0-RC1
======================

# Table of Contents

- [Remote configuration](#remove-configuration)
- [Plugins Discovery](#plugin-discovery)
- [Filename](#filenames)

### Remote configuration

Since 0.11.0 the bundle `OneupFlysystemBundle` was removed and replaced with a
simpler configuration (see nanbando/core#113).

**Before**: `nanbando.yml`

```
nanbando:
    storage:
        local_directory: "%home%/nanbando"
        remote_service: filesystem.remote

oneup_flysystem:
    adapters:
        remote:
            local:
                directory: "%home%/nanbando/remote"

    filesystems:
        remote:
            adapter: remote
            alias: filesystem.remote
            plugins:
                - filesystem.list_files
```

**After:**: `nanbando.yml`

```
nanbando:
    storage:
        local_directory: "%home%/nanbando"
        remote:
            local:
                directory: "%home%/nanbando/remote"
```

### Plugin Discovery

**Before**: `puli.json`

```
{
    "version": "1.0",
    "name": "<name>",
    "bindings": {
        "<uuid>": {
            "_class": "Puli\\Discovery\\Binding\\ClassBinding",
            "class": "<bundle-class>",
            "type": "nanbando/bundle"
        }
    }
}
```

**After**: `composer.josn`

```
{
    ...
    "extra": {
        "nanbando-bundle-class": "<bundle-class>"
    }
}
```

### Filenames

The filenames was changed in 0.5.0 (see nanbando/core#62)
from pattern `H-i-s-Y-m-d` to `Y-m-d-H-i-s`. Rename files
to fit the new pattern.
