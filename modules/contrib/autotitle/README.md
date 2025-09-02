# Autotitle

This module allows to automatically set node title from
the heading tags in content (H1-H6).

For a full description of the module, visit the
[project page](https://www.drupal.org/project/autotitle).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/autotitle).


## Table of contents

- Installation
- Requirements
- Configuration
- Uninstallation
- Maintainers


## Installation

- Extract the tar.gz into your 'modules' directory or get it
  via composer: composer require drupal/autotitle.
- Go to "Extend" after successfully login into admin.
- Enable the module at 'administer >> modules'.


## Requirements

This module requires no modules outside of Drupal core.


## Configuration

1. Go to /admin/structure/types/manage/[node_type].
2. Under the tab "Autotitle" there is an option to enable automatic titles.
   Check it to enable autotitle functionality. After
   enabling it, the title field will be hidden on your node form,
   but you can revert it at any time by visiting
   /admin/structure/types/manage/[node_type]/form-display.
3. The source field decides where the headings should be fetched from. The
   default  is the body field. Available fields are only
   string, string_* and text, text_* types
4. You can set the fallback title for cases in which there was no heading
   found in the source field.


## Uninstallation

1. Go to /admin/modules/uninstall and find autotitle module.
2. Uninstall the module


## Maintainers

- Mariusz Andrzejewski - [sayco](https://www.drupal.org/u/sayco)
- Marco Fernandes - [marcofernandes](https://www.drupal.org/u/marcofernandes)
- Henrik Akselsen - [henrikakselsen](https://www.drupal.org/u/henrikakselsen)
- Thor Andre Gretland - [thorandre](https://www.drupal.org/u/thorandre)
- Roberto Ornelas - [roborn](https://www.drupal.org/u/roborn)
