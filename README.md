## ElmDash Menu

Menus are but a simple tree structure. You can implement them a million ways, but this is a clean and minimal menu structure that handles the basic chores of setting active states and toggling menu items depending on a user's authorization within Laravel apps.

In your provider's boot method add something like:

```php
$m = new \ElmDash\Menu\Menu('top');

// a login route, only visible to guests
$m->add('access')->guests();

// menu items for users with proper permissions
$m->add('books.edit', function (Menu $books) {
	$books->can('edit-books');

	// It's optional to edit within callbacks.
	// However, this will be called once the menu is rendered
	// (instead of immediately)
});

// an adit route, active for any account routes
$m->add('account.edit')->match('account.*');

// you can also nest items
$c = $m->add('event.create');

// you may add route parameters that are 
// included when determining active states
$c->add('event.create')->params(['type' => 'basic']);
$c->add('event.create')->params(['type' => 'special']);

// more menu items
$m->add('logout');

$this->app->instance('menu-top', $m);
```

Then in your view:

```twig
{% set menu = app.make('menu-top') %}

<div container>
    <nav class="top">
        <div class="logo"></div>
        <nav>
            {% for item in menu.children %}
               {% set activeClass = item.isActive ? 'active' : '' %}
                <span>
                    <a href="{{ item.href }}" class="{{ activeClass }}">
                        {{ item.label }}
                    </a>
                </span>
                <nav>
                    {% for subitem in item.children %}
                        {% set activeClass = subitem.isActive ? 'active': '' %}
                        <span>
                            <a href="{{ subitem.href }}" class="{{ activeClass }}">
                                {{ subitem.label }}
                            </a>
                        </span>
                    {% endfor %}
                </nav>
            {% endfor %}
        </nav>
    </nav>
</div>
```

Labels are set in `lang/menu.php`:

```php
<?php

return [
  'top' => [
    'access'       => 'Sign up or sign in',
    'account-edit' => 'Settings',
    'event-create' => 'Create an event',
    'logout'       => 'Sign out',
  ],
];
```

Notice that the translation keys are the menu items' route names but with dashes instead of periods. Also, notice that the top-level name of the menu is used to group the translations. 

### Features

##### Root Menus

Top-level (root) menu objects will usually not be rendered and won't have a route to reference. You can give these routes names for convenience. 

```php
$menu = new Menu('top-left');
```

##### Adding items

The menu tree is made up of only `Menu` objects so every item in the tree has the same methods as every other item. That is, there is not "MenuRoot", "MenuGroup", or "MenuItem" differentiation. 

Adding an item can be as simple as adding the name of a route. (At this time, you can't add any routes that aren't named.)

```php
$menu->add('my.route');
```

This returns the newly created `Menu` child. You can use this for chaining. 

```php
$menu->add('my.route')->add('my.child.route');
```

It may be easier for several reasons, however, to use a callback to create your tree structures. First, it's more visually appealing and second, the functions are called once needed. This is handy for delaying menu initialization code until everything else has been initialized.

```php
$menu->add('my.route', function (Menu $m) {
  // runs eventually, but not right away
  $m->add('my.child.route');
});

// this would trigger all callbacks and load the whole tree (likely called in your view)
$children = $menu->children();
```

There are other means of adding items:

```php
// prepending
$menu->add('my.second.child');
$child = $menu->prepend('my.first.child');

// using groups (the name is _not_ a route name)
// they do not have a route associated with them. So you can't, for example, 
// attempt to get the href of a group. 
$child->group('some.group.name', function (Menu $g) {
	$g->add('my.grand.child');
});


// you can also prepend a group
$child->groupPrepend('some.other.group', function (Menu $g) {
	$g->add('my.step.grand.child');
});
```

##### Routes

The route name is typically specified when adding the menu item.

```php
$menu->add('my.first.route');

// or more manually
$child = $menu->add();
$child->route('my.route');
```

If your route takes parameters, you should add them to the menu. They will be used to render the correct href value and for determining the active menu item.

```php
$menu->add('my.route.default', function (Menu $m) {

  // if the param is optional
  $m->params(['type' => null]); 
});

$menu->add('my.route.type', function (Menu $m) {
  $m->params(['type' => 'fish']);
});

$menu->add('my.route.type', function (Menu $m) {
  $m->params(['type' => 'mammals']);
});
```

Getting the URL to render in your view is simply like this:

```twig 
<a href="{{ item.href }}" class="{{ item.isActive ? 'active' : '' }}">
    {{ item.label }}
</a>
```

Or if you need absolute URLs

```twig 
<a href="{{ item.href(true) }}" class="{{ item.isActive ? 'active' : '' }}">
    {{ item.label }}
</a>
```

##### Active menu items

When a child item is active, all parents will be considered active. To find out which menu item matches the currently active URL, we compare the current route's name with each menu item's route name. For example, the "users.account" menu item will be active when the "users.account" route is active. It's just a strict name (and possibly parameters) comparison.

However, you may have more than one view per your lowest level of menu items. You'll need a way to specify other named routes that should mark a menu item as active. For that we have globbing:

```php
// single glob
$users->activeFor('users.*');

// multi glob
$settings->activeFor('users.settings.*|account|account.edit.*');

// regex
$account->activeFor('/^users\.account\..*$/');
```

Again, we're only working with named routes, so make sure your routes are all named.

As shown above, to find out if an item is active in your view just call `item.isActive`. 

For submenus, you may also need to get the currently active item of some root menu object.

```twig
{% set menu = app.make('nav-top-right').activeChild %}
```

##### Access

Menu items can be hidden for users who don't have permission. This just uses Laravel's authorization policies. 

```php
$left->add('wiki.index')->can('edit-wiki');
```

Menu items that are not visible to the current user will not be returned by the `menu.children` function. If a parent item is not visible, then the children will also not be marked as visible. In submenus, you may need to check for visibility directly (like, if you are not looping directly over the results of `menu.children`. 

```twig
{% set menu = app.make('nav-top-left').activeChild %}

{% if not menu %}
    {% set menu = app.make('nav-top-right').activeChild %}
{% endif %}

{% if menu and menu.isVisible %}
    <nav class="nav nav-pills nav-stacked">

        {% for group in menu.children %}
            {% if group.isVisible %}
                <li class="nav-item nav-header">{{ group.label }}</li>

                {% for item in group.children %}
                    <li class="nav-item">
                        <a class="nav-link {{ item.isActive ? 'active' : '' }}" href="{{ item.href }}">
                            {{ item.label }}
                        </a>
                    </li>
                {% endfor %}

            {% endif %}
        {% endfor %}

    </nav>
{% endif %}
```

By default, all menu items require authentication. To create menu items for anonymous users:

```
$menu->add('photos.index')->guests();
```

##### Flags

You can add flags to a menu item for inquiring about menu items later. 

```php
// during initialization
$m->flags(['ajax-only', 'bold']);
```

```twig
// later in the view
{% if menu.is('ajax-only') %}
  {# ... do something different for this menu item #}
{% endif %}
```

##### Labels

Labels are just taken from the Laravel's translation infrastructure.

By default, the translation key is just the route name provided when adding the menu item, but with the dots converted to dashes

```php
$menu->add('my.route.name'); // key is "my-route-name"
```

If the top-level menu object has a name, then that will be prefixed to the translation key: i.e. 'top-left' and 'my.route.name' => 'top-left.my-route-name'.

```php
$menu = new Menu('top-left');
$menu->add('my.route.name'); // key is "top-left.my-route-name"
```

Group names are also prefixed

```php
$menu = new Menu('top-left');
$menu->group('branding', function (Menu $g) {
	$g->add('my.route.name'); // key is "top-left.branding.my-route-name"
});
```

At any place up the menu tree, you may specify a translation namespace.

```php
$menu = new Menu('top-left');
$menu->langNamespace('app');
$menu->add('my.route.name'); // key is "app::top-left.my-route-name"
```

You can override the language key to use:

```php
$menu->add('my.route.name', function (Menu $m) {
  $m->labelKey('my-full-route-name');
});
```

Or you can override the label directly

```php
$menu->add('my.route.name', function (Menu $m) {
  $m->label('My Route Here!');
});
```
