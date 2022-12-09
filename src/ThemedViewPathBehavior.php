<?php
/**
 * @link http://www.diemeisterei.de/
 * @copyright Copyright (c) 2022 diemeisterei GmbH, Stuttgart
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace dmstr\themedViewPath;

use yii\base\Controller;
use yii\base\Module;
use yii\base\Theme;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class ThemedViewPathBehavior
 * @package dmstr\themedViewPath
 * Author: Jens Giessmann <j.giessmann@herzogkommunikation.de>
 *
 * Behavior which provide easy way to set view pathMap via yii2 theming
 *
 */
class ThemedViewPathBehavior extends \yii\base\Behavior
{

    /**
     * Value for $pathOrder: given paths will be prepended to owners viewPath
     */
    const MAP_PREPEND = 'prepend';

    /**
     * Value for $pathOrder: given paths will be appended to owners viewPath
     */
    const MAP_APPEND = 'append';

    /**
     * additional view paths that should be set as pathMap via theming
     * can be a string or an array of paths
     *
     * @see \yii\base\Theme
     *
     * @var string|array
     */
    public $pathMap;

    /**
     * list of subDirs that should be added as pathMap alternatives for given viewPaths
     * useful for e.g. branded views
     * the self::useAsBasePath setting will be respected while building subDir paths
     *
     * @var null|string|array
     */
    public $subDirs;

    /**
     * if true, given paths in pathMap will be suffixed with basename($this->srcPath)
     * useful if behavior is defined in context where controller id is not fixed, e.g. module, controller baseClass, etc.
     *
     * @var bool
     */
    public $useAsBasePath = false;

    /**
     * optionally define path key for the theme pathMap.
     * If not set owner->getViewPath() will be used
     *
     * @var
     */
    public $srcPath;

    /**
     * define if given paths should be appended (default) or prepended to the owners viewPath
     * as this defines the order in which yii will try to get the view (first match), use this flag to define if owners
     * viewPath should be fallback (prepend) or alternative (append)
     *
     * @var string MAP_APPEND|MAP_PREPEND
     */
    public $pathOrder = self::MAP_APPEND;

    /**
     * declare handler for EVENT_* according to owner type
     *
     * @inheritdoc
     */
    public function events()
    {

        $events = [
            Controller::EVENT_BEFORE_ACTION => 'setThemedViewPath',
        ];

        if ($this->owner instanceof Module) {
            $events = [
                Module::EVENT_BEFORE_ACTION => 'setThemedViewPath',
            ];
        }

        if ($this->owner instanceof Widget) {
            $events = [
                Widget::EVENT_BEFORE_RUN => 'setThemedViewPath',
            ];
        }

        return $events;

    }

    /**
     * init and set pathMap via $app->view->theme
     *
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function setThemedViewPath()
    {

        $srcPath = $this->getOwnerSrcViewPath();

        if (empty($srcPath)) {
            \Yii::warning(__CLASS__ . ' called without valid path configs');
            return false;
        }

        if (empty($this->pathMap) && empty($this->subDirs)) {
            \Yii::warning(__CLASS__ . ' called without valid path configs (none of patchMap|subDirs is defined)');
            return false;
        }

        // get basePath from srcPath,
        // will be used for building map with useAsBasePath == true and for subDirs
        $srcBase = basename($srcPath);

        // init map
        $map = [];
        // preprend || append ?
        self::MAP_APPEND === $this->pathOrder && $map[] = $srcPath;

        // if defined, add given pathMap entries
        if (!empty($this->pathMap)) {
            // add additional dirs given via pathMap property
            // ensure array
            if (!is_array($this->pathMap)) {
                $this->pathMap = [$this->pathMap];
            }
            foreach ($this->pathMap as $path) {
                $map[] = $this->useAsBasePath ? implode(DIRECTORY_SEPARATOR, [rtrim($path, '/'), $srcBase]) : $path;
            }
        }

        // preprend || append ?
        self::MAP_PREPEND === $this->pathOrder && $map[] = $srcPath;

        // if defined, add given subDirs to the already defined paths
        // this is done here (and not within loop above), because this way we can add sudirs with or without additional pathMap entries
        if (!empty($this->subDirs)) {
            // create new map to preserve already defined path order
            $subDirMap = [];
            if (!is_array($this->subDirs)) {
                $this->subDirs = [$this->subDirs];
            }
            foreach ($map as $path) {
                foreach ($this->subDirs as $subdir) {
                    if ($this->useAsBasePath) {
                        $subDirMap[] = implode(
                            DIRECTORY_SEPARATOR,
                            [rtrim(dirname($path), '/'), basename($path), $subdir]
                        );
                    } else {
                        $subDirMap[] = implode(DIRECTORY_SEPARATOR, [rtrim($path, '/'), $subdir]);
                    }
                }
                $subDirMap[] = $path;
            }
            $map = $subDirMap;
        }

        $pathMap = [
            $srcPath => $map,
        ];

        if (\Yii::$app->view->theme) {
            \Yii::$app->view->theme->pathMap = ArrayHelper::merge(\Yii::$app->view->theme->pathMap, $pathMap);
        } else {
            \Yii::$app->view->theme = \Yii::createObject(
                [
                    'class' => Theme::class,
                    'pathMap' => $pathMap,
                ]
            );
        }

        return true;
    }

    /**
     * get path from given self::srcPath or owner->getViewPath()
     *
     * @return false
     */
    protected function getOwnerSrcViewPath()
    {
        if (!empty($this->srcPath)) {
            return $this->srcPath;
        }

        // as Module is not instanceof ViewContextInterface (like controllers or widgets)
        // we must check via owner->hasMethod here.
        if ($this->owner->hasMethod('getViewPath')) {
            return $this->owner->getViewPath();
        }

        return false;
    }


}