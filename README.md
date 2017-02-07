## ElmDash Menu

Menus are but a simple tree structure. You can implement them a million ways, but this is a clean and minimal menu structure that handles the basic chores of setting active states and toggling menu items depending on a user's authorization within Laravel apps.

In your provider's boot method add something like:

```
$m = new \ElmDash\Menu();

// a login route, only visible to guests
$m->add('access')->guests();

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

```
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

```
<?php

return [
    'access'       => 'Sign up or sign in',
    'account.edit' => 'Settings',
    'event.create' => 'Create an event',
    'logout'       => 'Sign out',
];

```
