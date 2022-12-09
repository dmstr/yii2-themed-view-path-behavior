# yii2-themed-view-path-behavior

Behavior to easily set additional view paths via [yii2 theme pathMap](https://www.yiiframework.com/doc/guide/2.0/en/output-theming).

Config options see: [ThemedViewPathBehavior.php](./src/ThemedViewPathBehavior.php)

ThemedViewPathBehavior can be attached to:
- controllers
- modules
- widgets

## path order

As the yii\base\Theme will use the first matching view file from the list of given paths, 
the order within the generated pathMap is relevant.

see: [guide: theme-inheritance](https://www.yiiframework.com/doc/guide/2.0/en/output-theming#theme-inheritance)

In the context of this behavior, there are basically 2 scenarios:

_Should the owners default viewPath be the first or the last directory where yii will search for view files?_

The `pathOrder` property can be used to define the order:

| value | effect                                                   |
|-------|----------------------------------------------------------|
| ThemedViewPathBehavior::MAP_APPEND | the owners default viewPath will be the **first** dir in map |
| ThemedViewPathBehavior::MAP_PREPEND | the owners default viewPath will be the **last** dir in map  |


## used Events

According to the type of the `owner` the behavior attach itself to events:

| owner | event |
| ------|------|
| controller | Controller::EVENT_BEFORE_ACTION |
| module | Module::EVENT_BEFORE_ACTION |
| widget | Widget::EVENT_BEFORE_RUN |


## Examples:

### Extend viewPath for one controller

Task:
- You have installed the dmstr/yii2-active-record-search
- You want to integrate a SearchGroupController to manage the SearchGroups from active-record-search module within another module e.g. admin
- You want to overwrite some (but not all) default views used from within \dmstr\activeRecordSearch\controllers\SearchGroupController

Solution:
- set active-record-search module view path as fallback via behavior config.

```php
<?php

namespace project\modules\admin\controllers;

use dmstr\themedViewPath\ThemedViewPathBehavior;

class SearchGroupController extends \dmstr\activeRecordSearch\controllers\SearchGroupController
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['themedViewPath'] = [
            'class' => ThemedViewPathBehavior::class,
            'pathMap' => '@vendor/dmstr/yii2-active-record-search/src/views/search-group',
            'useAsBasePath' => false,
        ];

        return $behaviors;

    }

    public function actionCreate()
    {
        return "Action is not available in this context";
    }

    public function actionDelete($id)
    {
        return "Action is not available in this context";
    }
}
```

generated pathMap for ProductController:
```
[
    '/app/project/src/modules/admin/views/search-group' => [
        0 => '/app/project/src/modules/admin/views/search-group'
        1 => '@vendor/dmstr/yii2-active-record-search/src/views/search-group'
    ]
]
```

### Extend view Path for all (child) controllers via BaseController

Task:
- You have an admin module where you define controllers to manage AR Models created with giiant within a cruds module
- Your admin controllers have names (ids) that are also defined in the cruds module
- You want to overwrite some (but not all) default views from the generated cruds


Solution: 
- add behavior to a BaseController used by Controllers which should use the views from the cruds module as fallback and define the basePath as pathMap.
- the controller ID will be appended within behavior

```php
<?php

namespace project\modules\admin\controllers;

use dmstr\themedViewPath\ThemedViewPathBehavior;
use yii\web\Controller;

class BaseController extends Controller
{
    #....
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['themedViewPath'] = [
            'class' => ThemedViewPathBehavior::class,
            'pathMap' => '@project/modules/cruds/views',
            'useAsBasePath' => true,
        ];

        return $behaviors;

    }
    #....
```

generated pathMap for ProductController:
```
[
    '/app/project/src/modules/admin/views/product' => [
        1 => '/app/project/src/modules/admin/views/product'
        3 => '@project/modules/cruds/views/product'
    ]
]
```

### Extend view Path for all (child) controllers via BaseController with "branded" subDirs

Task:
- same as above, but with additional subdir(s) where you can store "branded" views

Solution:
- same as above, but add another path with "branded" name

```php
<?php

namespace project\modules\admin\controllers;

use dmstr\themedViewPath\ThemedViewPathBehavior;
use project\components\ApplicationHelper;
use yii\web\Controller;

class BaseController extends Controller
{
    #....
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['themedViewPath'] = [
            'class' => ThemedViewPathBehavior::class,
            'pathMap' => [
                '@project/modules/cruds/views',
            ],
            'subDirs' => [
                ApplicationHelper::brand(),
            ],
            'useAsBasePath' => true,
        ];

        return $behaviors;

    }
    #....
```

generated pathMap for ProductController:
```
[
    '/app/project/src/modules/admin/views/product' => [
        0 => '/app/project/src/modules/admin/views/product/customer-name'
        1 => '/app/project/src/modules/admin/views/product'
        2 => '@project/modules/cruds/views/product/customer-name'
        3 => '@project/modules/cruds/views/product'
    ]
]
```

### add extended view path for all controllers via Module

Task:
- you have a module with default (e.g. autogenerated) view files in the default `./views/` dir of the module
- you want to overwrite some (but not all) default views with files in a `./views-extended/` dir of the module

Solution:
- add behavior to the Module class
- define the *-extended dir in `pathMap`
- set the `pathOrder` to `ThemedViewPathBehavior::MAP_PREPEND` as yii should search first in the given`./views-extended/` and use the default viewPath as fallback

```PHP
<?php

namespace app\modules\cruds;

use dmstr\themedViewPath\ThemedViewPathBehavior;

/**
 *  module definition class.
 */
class Module extends \yii\base\Module
{
    #....
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['themedViewPath'] = [
            'class' => ThemedViewPathBehavior::class,
            'pathMap' => $this->getViewPath() . '-extended',
            'pathOrder' => ThemedViewPathBehavior::MAP_PREPEND,
        ];

        return $behaviors;

    }
    #....

}
```

generated pathMap for Controllers within this module:
```PHP
[
    '/app/project/src/modules/cruds/views' => [
        0 => '/app/project/src/modules/cruds/views-extended'
        1 => '/app/project/src/modules/cruds/views'
    ]
]
```


### add alternative branded/themed view path for a widget

Task:
- you have a widget and want to overwrite (some) views according to defined brand via subdirs per brand

Solution:
- add behavior to the Widget class
- define subDirs for current context (via ApplicationHelper::brand() in this example)

```PHP
<?php

namespace project\modules\frontend\widgets\careerportal;

use dmstr\themedViewPath\ThemedViewPathBehavior;
use project\components\ApplicationHelper;

class LatestJobs extends \yii\base\Widget
{

    #....
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['themedViewPath'] = [
            'class' => ThemedViewPathBehavior::class,
            'subDirs' => [
                ApplicationHelper::brand(),
            ],
        ];

        return $behaviors;

    }
    #....
}
```

generated pathMap for the widget:
```
[
    '/app/project/src/modules/frontend/widgets/careerportal/views' => [
        0 => '/app/project/src/modules/frontend/widgets/careerportal/views/customer-name'
        1 => '/app/project/src/modules/frontend/widgets/careerportal/views'
    ]
]
```