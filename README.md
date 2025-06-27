[![Moodle Plugin CI](https://github.com/edu-sharing/moodle-mod_edusharing/actions/workflows/moodle-plugin-ci.yml/badge.svg)](https://github.com/edu-sharing/moodle-mod_edusharing/actions/workflows/moodle-plugin-ci.yml)
# edu-sharing activity module

The edu-sharing activity module adds a new option to the activities/resource menu. Using the edu-sharing resource allows you to either pick content from the repository or upload it to a folder of the repository. You may pick which version of the content you would like to provide in the course (always the latest vs. the version you just picked).

The edu-sharing activity module is also needed as a dependency for all other edu-sharing plugins (filter, submission, editor). 

## Requirements

Please note: Plugin versions > 8.0.0 require an environment running PHP 8.0 or higher

## Installation

The Edu-sharing activity module is available on the Moodle Plugins Repository: [Link](https://moodle.org/plugins/mod_edusharing). It can be installed directly to your Moodle deployment or manually by using the downloadable zip file.

Advanced admins can also use git to install the plugin directly on their server:

```
cd $YOUR_MOODLE_BASEDIR/mod
git clone https://github.com/edu-sharing/moodle-mod_edusharing.git edusharing
cd edusharing
git submodule init
git submodule update
```

After installation connect the activity module to an edu-sharing repository (plugin settings / repository settings)
For a full documentation with screenshots of the post installation steps for the edu-sharing plugin package visit the [documentation pages](http://docs.edu-sharing.com/confluence/edp/en).

## Update note

For changes please refer to the changelog in this repository

## Documentation

More information can be found on the [homepage](http://www.edu-sharing.com).

## Where can I get the latest release?

You find our latest releases on our [github repository](https://github.com/edu-sharing).

## Contributing

If you plan to contribute on a regular basis, please visit our [community site](http://edu-sharing-network.org/?lang=en).

## License

Code is under the [GNU GENERAL PUBLIC LICENSE v3](./LICENSE).
