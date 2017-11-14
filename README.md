# Installation

`composer require agencewonderful/wwp-contact;`

# Activation

From command line:
`vendor/bin/wp plugin activate wwp-contact;`

Or from the backend, plugins, activate the plugin

# Override

This is a vendor, DO NOT MODIFY the core plugin. To modify the plugin, extend it from the child theme.

- Create a `plugins` folder in the child theme if it doesn't exist yet
- Create a `wwp-contact` folder inside this plugins folder
- Create an `includes` folder inside this wwp-contact folder
- Create a child plugin manager inside this includes folder, for example ContactThemeManager.php
- Make this `ContactThemeManager` extend `ContactManager` and make sure the namespace is different than the parent one, for example WonderWp\Plugin\Contact\Child
- To tell the application to use this new class instead of the old one, define the `WWP_PLUGIN_CONTACT_MANAGER` constant inside the /config/application.php file like so. `define('WWP_PLUGIN_WWP_PLUGIN_CONTACT_MANAGER_MANAGER',\WonderWp\Plugin\Contact\Child\ContactThemeManager::class);`
- Add this new folder to the autoload / PSR4 section in the composer.json file:

```
    "autoload": {
        "psr-4": {
            "WonderWp\\Theme\\Components\\": "web/app/themes/pg/styleguide/components/components/componentsClasses",
            "WonderWp\\Theme\\Child\\": "web/app/themes/pg/includes",
            "WonderWp\\Plugin\\Contact\\Child\\": "web/app/themes/wwp_child_theme/plugins/wwp-contact/includes"
        }
    },
```
- Tell composer to reload its autoload index : `composer dump-autoload`
- Override the `register` method to redeclare the different configurations and services. for example:

```
    public function register(Container $container)
    {
        parent::register($container);

        //Register your own Services, that will be used instead of the default ones.

        $this->addService('erMail',function(){
            //Hook service
            return new ContactThemeMailService();
        });
    }

```
- Once you have a child plugin manager, you can override many things, the entity used, the form used, the different services used (hooks, mails, form handlers...), the plugin config...
- You can override template files by creating them in your theme plugin directory, replicating their path in the core plugin folder. For example, if you'd like to override the `wwp-contact/public/views/form.php` that is inside the plugin folder, create the `wwp-contact/public/views/form.php` file inside the child theme folder

# Email Design
- By default there's a mail template that will be used as the mail HTML structure. It's located in the parent theme under `/templates/amil/default.php`. You can override this in your child theme but usually that's not necessary.
- This default mail template tries to locate the main theme css color variable and will use this to draw a border on the mail design to remind of the theme's design.
- If you place a logo in the child theme here `/asset/raw/images/logo-mail.png`, it will be used as is in the top of the mail design.

# Customizing mail content
- If you want to customize the title and the content of an email for a specific form :
- Go to the Translation section, under the "Wwp contact" Plugin and choose "Edit"
- Create a custom key for each form type, one for the title and one for the content, using [formid] to identify the form type.
- [formid] can be found in the Contact form list under the "ID" column
- Title format key : `new.receipt.msg.title.form-[formid]`
- Content format key : `new.receipt.msg.content.form-[formid]`


# Available Hooks
- If you want to add some informations to the contact : `wwp-contact.contact_handler.contact_created`
- When the contact form is submitted and valid : `wwp-contact.contact_handler_service_success` | Filter
- If you want to modify the message that is sent to the admin : `wwp-contact.contact_mail_content` | Filter
- If you want to modify the message that is sent to the user : `wwp-contact.contact_receipt_mail_content` | Filter
- If you want to modify the contact form after its creation : `wwp-contact.contact_form.created` | Filter

# Passing data to a form
You can pass data to a form either via shortcode or via get parameters.
- Via the shortcode, use the `values` shortcode attribute and provide a quesry string like so : `values="ref=testref&sujet=Info`
- Via get parameters, use the  `values` get parameter like so : `?values[ref]=testref&values[sujet]=Info`


# Notable Changelog
- At version 1.2.1 ,the ContactEntity structure has been change, that wil cause some mysql errors, but that's easily fixable.
