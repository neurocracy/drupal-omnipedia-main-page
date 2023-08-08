This contains the source files for the "*Omnipedia - Main page*" Drupal module,
which provides main page functionality for [Omnipedia](https://omnipedia.app/).

⚠️ ***[Why open source? / Spoiler warning](https://omnipedia.app/open-source)***

----

# Requirements

* [Drupal 10](https://www.drupal.org/download)

* PHP 8.1

* [Composer](https://getcomposer.org/)

## Drupal dependencies

Follow the Composer installation instructions for these dependencies first:

* The [`omnipedia_core`](https://github.com/neurocracy/drupal-omnipedia-core) and [`omnipedia_date`](https://github.com/neurocracy/drupal-omnipedia-date) modules.

----

# Installation

## Composer

### Set up

Ensure that you have your Drupal installation set up with the correct Composer
installer types such as those provided by [the `drupal/recommended-project`
template](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates#s-drupalrecommended-project).
If you're starting from scratch, simply requiring that template and following
[the Drupal.org Composer
documentation](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
should get you up and running.

### Repository

In your root `composer.json`, add the following to the `"repositories"` section:

```json
"drupal/omnipedia_date": {
  "type": "vcs",
  "url": "https://github.com/neurocracy/drupal-omnipedia-date.git"
}
```

### Installing

Once you've completed all of the above, run `composer require
"drupal/omnipedia_date:4.x-dev@dev"` in the root of your project to have
Composer install this and its required dependencies for you.
