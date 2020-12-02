UPGRADE before 1.0-RC1
======================

# Table of Contents

- [Plugins Discovery](#plugin-discovery)
- [Filename](#filenames)

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
