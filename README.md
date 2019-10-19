# OriginPHP Upgrade Plugin (beta)

This is a plugin to assist upgrading from 1.x to 2.0. 

It does the following

1. convert the old folder structure to the new structure and update USE statements
2. update Namespace changes 
3. warn you of features that you are using that might have changed/removed or require action 

## Usage

1. Make sure you have removed all previous deprecation warnings from your app using version 1.33 or higher.
2. Backup your files
3. create a new project 

```linux
$ composer create-project originphp/app app-v2
```

4. install this pluign

```linux
$ cd app-v2
$ composer require originphp/upgrade
```

5. Copy the contents of your `app` folder into the `app` folder (leave structure as is)

6. Copy the contents of your `tests` folder into the `tests` folder (leave structure as is)

7. Do a DRY run

```linux
$ bin/console upgrade --dry-run
```

This will show you what it will change automatically and possible issues that need to be looked
due to changes ( model callbacks, controller callbacks, composer dependencies, or public properties)