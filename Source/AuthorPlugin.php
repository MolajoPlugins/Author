<?php
/**
 * Author Plugin
 *
 * @package    Molajo
 * @copyright  2014-2015 Amy Stephen. All rights reserved.
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 */
namespace Molajo\Plugins\Author;

use CommonApi\Event\DisplayEventInterface;
use CommonApi\Event\ReadEventInterface;
use Molajo\Plugins\ReadEvent;
use stdClass;

/**
 * Author Plugin
 *
 * @package  Molajo
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @since    1.0
 */
final class AuthorPlugin extends ReadEvent implements ReadEventInterface, DisplayEventInterface
{
    /**
     * Author Id
     *
     * @var    integer
     * @since  1.0.0
     */
    protected $author_id;

    /**
     * Fires after read for each row
     *
     * @return  $this
     * @since   1.0.0
     * @throws  \CommonApi\Exception\RuntimeException
     */
    public function onAfterReadRow()
    {
        if ($this->checkOnAfterReadRowProcessPlugin() === false) {
            return $this;
        }

        $this->processOnAfterReadRowPlugin();

        return $this;
    }

    /**
     * Prepare Data for Injecting into Template
     *
     * @return  $this
     * @since   1.0.0
     */
    public function onGetTemplateData()
    {
        if ($this->checkOnGetTemplateDataProcessPlugin() === false) {
            return $this;
        }

        $this->processOnGetTemplateDataPlugin();

        return $this;
    }

    /**
     * Should plugin be executed for onAfterReadRow?
     *
     * @return  boolean
     * @since   1.0.0
     */
    protected function checkOnAfterReadRowProcessPlugin()
    {
        if (isset($this->runtime_data->application->id)) {
            if ((int)$this->runtime_data->application->id === 0) {
                return false;
            }
        }

        if (isset($this->controller['row']->extension_instances_id)) {
            if ((int)$this->controller['row']->extension_instances_id === 3000
                || (int)$this->controller['row']->extension_instances_id === 17000
            ) {
                return false;
            }
        }

        if (isset($this->controller['row']->catalog_catalog_type_id)) {
            if ((int)$this->controller['row']->catalog_catalog_type_id === 3000
                || (int)$this->controller['row']->catalog_catalog_type_id === 17000
            ) {
                return false;
            }
        }

        if (isset($this->controller['row']->created_by)) {
        } else {
            return false;
        }

        return true;
    }

    /**
     * Should plugin be executed for onGetTemplateData?
     *
     * @return  boolean
     * @since   1.0.0
     */
    protected function checkOnGetTemplateDataProcessPlugin()
    {
        if (strtolower($this->controller['parameters']->token->name) === 'author') {
        } else {
            return false;
        }

        if (isset($this->controller['parameters']->token->attributes['author'])) {
        } else {
            return false;
        }

        return true;
    }

    /**
     * Get Author Profile
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function processOnAfterReadRowPlugin()
    {
        $this->author_id = $this->controller['row']->created_by;

        if ($this->getAuthorCache() === true) {
        } else {
            $this->executeAuthorQuery();
        }

        $this->setAuthorData();

        $this->setAuthorCache();

        return $this;
    }

    /**
     * Process onGetTemplateData
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function processOnGetTemplateDataPlugin()
    {
        $this->author_id = $this->controller['parameters']->token->attributes['author'];

        if ($this->getAuthorCache() === true) {
        } else {
            $this->executeAuthorQuery();
            $this->setAuthorCache();
        }

        $cache_key  = $this->getAuthorCacheKey();

        $this->plugin_data->{strtolower($this->controller['parameters']->token->model_name)}
            = $this->plugin_data->$cache_key;

        return $this;
    }

    /**
     * Get Author Cache
     *
     * @return  boolean
     * @since   1.0.0
     */
    protected function getAuthorCache()
    {
        $cache_key  = $this->getAuthorCacheKey();

        if (is_object($this->plugin_data->$cache_key)) {
            return true;
        }

        if ($this->usePluginCache() === false) {
            return false;
        }

        $cache_item = $this->getPluginCache($cache_key);

        if ($cache_item->isHit() === false) {
            return false;
        }

        $this->plugin_data->$cache_key = $cache_item->getValue();

        return true;
    }

    /**
     * Execute Author Query
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function executeAuthorQuery()
    {
        $this->setAuthorQuery();

        $author_object                 = new stdClass();
        $author_object->data           = $this->runQuery();
        $author_object->model_registry = $this->query->getModelRegistry();

        $key = $this->getAuthorCacheKey();

        $this->plugin_data->$key = $author_object;

        return $this;
    }

    /**
     * Set Author Cache
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function setAuthorCache()
    {
        if ($this->usePluginCache() === false) {
            return $this;
        }

        $cache_key = $this->getAuthorCacheKey();

        $this->setPluginCache($cache_key, $this->plugin_data->$cache_key);

        return $this;
    }

    /**
     * Set Author Profile Data into Primary Row
     *
     * @param   array $model_registry
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function setAuthorData(array $model_registry = array())
    {
        $cache_key            = $this->getAuthorCacheKey();
        $author_object        = $this->plugin_data->$cache_key;
        $this->row            = $author_object->row;
        $this->model_registry = $author_object->model_registry;

        $this->setAuthorModelRegistryFields($model_registry);
        $this->setAuthorModelRegistryCustomFields($model_registry);

        return $this;
    }

    /**
     * Get Author Cache Key
     *
     * @return  string
     * @since   1.0.0
     */
    protected function getAuthorCacheKey()
    {
        return 'Author-' . (int)$this->author_id;
    }

    /**
     * Get Author Profile Query Object
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function setAuthorQuery()
    {
        $this->setQueryController('Molajo//Model//Datasource//User.xml');

        $this->setQueryControllerDefaults(
            $process_events = 1,
            $query_object = 'item',
            $get_customfields = 1,
            $use_special_joins = 1,
            $use_pagination = 0,
            $check_view_level_access = 1,
            $get_item_children = 0
        );

        $prefix = $this->query->getModelRegistry('primary_prefix', 'a');

        $this->query->where('column', $prefix . '.id', '=', 'integer', (int)$this->author_id);

        return $this;
    }

    /**
     * Add Author Fields to Model Registry
     *
     * @param   array $model_registry
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function setAuthorModelRegistryFields(array $model_registry = array())
    {
        $fields            = $model_registry['fields'];
        $customfieldgroups = $model_registry['customfieldgroups'];

        $new_fields = array();

        foreach ($fields as $field) {
            if (in_array($field['name'], $customfieldgroups)) {
            } else {
                $new_fields[$field['name']] = $field;
            }
        }

        $this->setAuthorFields('fields', $new_fields);

        return $this;
    }

    /**
     * Add Author Custom Fields to Model Registry
     *
     * @param   array $model_registry
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function setAuthorModelRegistryCustomFields(array $model_registry = array())
    {
        $customfieldgroups = $model_registry['customfieldgroups'];

        if (count($customfieldgroups) === 0) {
            return $this;
        }

        foreach ($customfieldgroups as $group) {
            $this->setAuthorFields($group, $model_registry[$group]);
        }

        return $this;
    }

    /**
     * Process Author Fields by Group
     *
     * @param   string $source
     * @param   array  $fields
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function setAuthorFields($source, array $fields = array())
    {
        if (count($fields) === 0) {
            return $this;
        }

        foreach ($fields as $field) {

            $name            = strtolower($field['name']);
            $field['source'] = 'fields';

            if ($source === 'fields') {
                $value = $this->row->$name;
            } else {
                $value = $this->row->$source->$name;
            }

            $this->setAuthorField($field, $value);
        }

        return $this;
    }

    /**
     * Save Author Fields
     *
     * @param   array $field
     * @param   mixed $value
     *
     * @return  $this
     * @since   1.0.0
     */
    protected function setAuthorField($field, $value)
    {
        $new_field           = $field;
        $new_field['name']   = 'author_' . $field['name'];
        $new_field['value']  = $value;
        $new_field['source'] = 'fields';

        $this->setField($new_field['name'], $new_field['value'], $new_field);

        return $this;
    }
}
