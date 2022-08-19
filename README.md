# yii2-themed-view-path-behavior

Behavior to easily set additional view paths via [yii2 theme pathMap](https://www.yiiframework.com/doc/guide/2.0/en/output-theming).



Config options see: [ThemedViewPathBehavior.php](./src/ThemedViewPathBehavior.php)

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

### Extend view Path for all controllers via BaseController

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

### Extend view Path for all controllers via BaseController with "branded" subDirs

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
