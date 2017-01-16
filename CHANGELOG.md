# Change Log

## [0.5.0](https://github.com/nanbando/core/tree/0.5.0) (2017-01-16)
[Full Changelog](https://github.com/nanbando/core/compare/0.4.0...0.5.0)

**Implemented enhancements:**

- Changed filename of backup [\#62](https://github.com/nanbando/core/pull/62) ([wachterjohannes](https://github.com/wachterjohannes))

**Closed issues:**

- Change names of backup [\#59](https://github.com/nanbando/core/issues/59)
- Environment variables [\#53](https://github.com/nanbando/core/issues/53)

**Merged pull requests:**

- Added environment to generate filename [\#63](https://github.com/nanbando/core/pull/63) ([wachterjohannes](https://github.com/wachterjohannes))

## [0.4.0](https://github.com/nanbando/core/tree/0.4.0) (2017-01-15)
[Full Changelog](https://github.com/nanbando/core/compare/0.3.0...0.4.0)

**Implemented enhancements:**

- Restore absolute path not working [\#52](https://github.com/nanbando/core/issues/52)

**Fixed bugs:**

- Fixed restore absolute path [\#61](https://github.com/nanbando/core/pull/61) ([wachterjohannes](https://github.com/wachterjohannes))

**Merged pull requests:**

- Improved storage-interface [\#58](https://github.com/nanbando/core/pull/58) ([wachterjohannes](https://github.com/wachterjohannes))
- Added path to information-command [\#57](https://github.com/nanbando/core/pull/57) ([wachterjohannes](https://github.com/wachterjohannes))
- Added latest option to information-command [\#56](https://github.com/nanbando/core/pull/56) ([wachterjohannes](https://github.com/wachterjohannes))
- Added requirements to documentation [\#55](https://github.com/nanbando/core/pull/55) ([wachterjohannes](https://github.com/wachterjohannes))

## [0.3.0](https://github.com/nanbando/core/tree/0.3.0) (2016-12-01)
[Full Changelog](https://github.com/nanbando/core/compare/0.2.0...0.3.0)

**Implemented enhancements:**

- Disable auto-completion in `fetch` command [\#47](https://github.com/nanbando/core/issues/47)
- Update doc to fit sulu 1.3 workspace configuration [\#37](https://github.com/nanbando/core/issues/37)

**Fixed bugs:**

- PHAR package doesn't work with relative local\_directory values  [\#32](https://github.com/nanbando/core/issues/32)

**Closed issues:**

- Sulu Preset [\#45](https://github.com/nanbando/core/issues/45)
- Presets backup [\#41](https://github.com/nanbando/core/issues/41)

**Merged pull requests:**

- Fixed multi-select questions [\#51](https://github.com/nanbando/core/pull/51) ([wachterjohannes](https://github.com/wachterjohannes))
- Added environment variable for nanbando dir [\#50](https://github.com/nanbando/core/pull/50) ([wachterjohannes](https://github.com/wachterjohannes))
- Check existance of remote-backup before upload [\#49](https://github.com/nanbando/core/pull/49) ([wachterjohannes](https://github.com/wachterjohannes))
- Disabled auto-completition [\#48](https://github.com/nanbando/core/pull/48) ([wachterjohannes](https://github.com/wachterjohannes))
- Implemented event-architecture [\#43](https://github.com/nanbando/core/pull/43) ([wachterjohannes](https://github.com/wachterjohannes))
- Added realpath call to resolve local-directory [\#39](https://github.com/nanbando/core/pull/39) ([wachterjohannes](https://github.com/wachterjohannes))
- Added sulu 1.3 configuration [\#38](https://github.com/nanbando/core/pull/38) ([wachterjohannes](https://github.com/wachterjohannes))

## [0.2.0](https://github.com/nanbando/core/tree/0.2.0) (2016-09-23)
[Full Changelog](https://github.com/nanbando/core/compare/0.1.1...0.2.0)

**Implemented enhancements:**

- Exception handling of plugins [\#16](https://github.com/nanbando/core/issues/16)

**Closed issues:**

- Migrate old backups from 0.1.1 to 0.2 [\#33](https://github.com/nanbando/core/issues/33)
- Fetch latest option [\#11](https://github.com/nanbando/core/issues/11)

**Merged pull requests:**

- Fixed restore if destination file already exists [\#36](https://github.com/nanbando/core/pull/36) ([wachterjohannes](https://github.com/wachterjohannes))
- Fixed \#33 introduced default values for new database values [\#35](https://github.com/nanbando/core/pull/35) ([wachterjohannes](https://github.com/wachterjohannes))
- Introduced project parameter [\#34](https://github.com/nanbando/core/pull/34) ([wachterjohannes](https://github.com/wachterjohannes))
- Added latest option to fetch command [\#31](https://github.com/nanbando/core/pull/31) ([wachterjohannes](https://github.com/wachterjohannes))
- Added exception handling for plugins [\#29](https://github.com/nanbando/core/pull/29) ([wachterjohannes](https://github.com/wachterjohannes))

## [0.1.1](https://github.com/nanbando/core/tree/0.1.1) (2016-07-21)
[Full Changelog](https://github.com/nanbando/core/compare/0.1.0...0.1.1)

**Closed issues:**

- Remove fork for embedded-composer [\#28](https://github.com/nanbando/core/issues/28)

## [0.1.0](https://github.com/nanbando/core/tree/0.1.0) (2016-06-30)
**Implemented enhancements:**

- Replace TemporaryFilemanager with library [\#26](https://github.com/nanbando/core/issues/26)
- Reconfigure command uses lock file by default [\#25](https://github.com/nanbando/core/issues/25)
- Introduce basic unit tests [\#9](https://github.com/nanbando/core/issues/9)

**Closed issues:**

- Docs: Create ~/.nanbando.yml before running nanbando self-update [\#21](https://github.com/nanbando/core/issues/21)
- Introduce a read-only flysystem adapter [\#12](https://github.com/nanbando/core/issues/12)
- Change name of update command to reconfigure [\#10](https://github.com/nanbando/core/issues/10)
- Restore command for directory-plugin [\#8](https://github.com/nanbando/core/issues/8)
- Place puli.json file into .nanbando folder [\#7](https://github.com/nanbando/core/issues/7)
- Add import for json-loader [\#3](https://github.com/nanbando/core/issues/3)
- Remove temp-files on exit [\#2](https://github.com/nanbando/core/issues/2)

**Merged pull requests:**

- Replaced own temporary-filesystem implementation with third party lib [\#27](https://github.com/nanbando/core/pull/27) ([wachterjohannes](https://github.com/wachterjohannes))
- Changed requirement of global configuration to optional [\#22](https://github.com/nanbando/core/pull/22) ([wachterjohannes](https://github.com/wachterjohannes))
- Implemented storage adapter [\#20](https://github.com/nanbando/core/pull/20) ([wachterjohannes](https://github.com/wachterjohannes))
- Implemented readonly adapter for flysystem [\#18](https://github.com/nanbando/core/pull/18) ([wachterjohannes](https://github.com/wachterjohannes))
- Added paramters and import section in json-loader [\#17](https://github.com/nanbando/core/pull/17) ([wachterjohannes](https://github.com/wachterjohannes))
- Applied fixes from StyleCI [\#14](https://github.com/nanbando/core/pull/14) ([wachterjohannes](https://github.com/wachterjohannes))
- Init testsuite [\#13](https://github.com/nanbando/core/pull/13) ([wachterjohannes](https://github.com/wachterjohannes))
- Initialized docs [\#5](https://github.com/nanbando/core/pull/5) ([wachterjohannes](https://github.com/wachterjohannes))
- Replaced composer-plugin with own implementation [\#4](https://github.com/nanbando/core/pull/4) ([wachterjohannes](https://github.com/wachterjohannes))



\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*