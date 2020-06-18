<?php

class Fastly_Edgemodules
{
    const SETTINGS = 'fastly-settings-edgemodules';

    private static $instance;

    protected function __construct() {}

    protected function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function renderSettings()
    {
        add_thickbox();
        $modules = $this->getModulesWithData();
        ?>
        <div class="wrap">
            <div id="fastly-admin" class="wrap">
                <h1>
                    <img alt="fastly" src="<?php echo FASTLY_PLUGIN_URL . 'static/logo_white.gif'; ?>"><br>
                    <span style="font-size: x-small;">version: <?php echo FASTLY_VERSION; ?></span>
                </h1>
            </div>
            <table class="form-table">
                <tbody>
                <?php foreach ($modules as $module): ?>
                    <tr>
                        <td>
                            <strong><?php echo $module->name; ?></strong><br>
                            <p>
                                <em><?php echo $module->description; ?></em>
                            </p>
                        </td>
                        <td nowrap="nowrap">
                            <em>
                                <strong><?php echo ($module->enabled) ? __('Enabled') : __('Disabled'); ?></strong><br>
                                Last updated: <?php echo isset($module->data['uploaded_at']) ? date ( 'Y/m/d' , strtotime($module->data['uploaded_at'])) : __('never'); ?>
                            </em>
                        </td>
                        <td nowrap="nowrap">
                            <a href="#TB_inline?&width=800&inlineId=fastly-edge-module-<?php echo $module->id; ?>" title="<?php echo $module->name; ?>" class="button thickbox">Manage</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <span class="spinner" id="html-popup-spinner" style="position: absolute; top:0;left:0;width:100%;height:100%;margin:0; background-color: #fff; background-position:center;"></span>
        </div>
        <?php foreach ($modules as $module): ?>
        <div id="fastly-edge-module-<?php echo $module->id; ?>" style="display:none;">
            <form action="<?php menu_page_url( 'fastly-edge-modules' ) ?>" method="post">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('fastly-edge-modules'); ?>">
                <table class="form-table">
                    <tbody>
                    <?php foreach($module->properties as $property): ?>
                        <?php if($property->type === 'group'): ?>
                            <?php $this->renderGroup($property, $module->data[$property->name], $module->id); ?>
                        <?php else: ?>
                            <?php $this->renderProperty($module->id, $property, $module->data[$property->name]); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- ToDo: will most likely require a proper js file -->
                <!-- ToDo: will require import of Handlebars library -->
                <!-- ToDo: Form submission in 2 steps:
                   1. parse values with Handlebars to generate snippet and dynamically add to form (maybe as value of hidden field?)
                   2. from backend push snippet to Fastly, and on success store values on DB
                -->
                <input type="submit" value="Upload">
            </form>
        </div>
        <?php endforeach; ?>
        <?php
    }

    protected function getModulesWithData()
    {
        $apiData = fastly_api()->get_all_snippets();
        $localData = get_option(self::SETTINGS, []);
        return array_map(function ($module) use ($apiData, $localData) {
            $module->data = isset($localData[$module->id]) ? $localData[$module->id] : [];
            $query = 'edgemodule_'.$module->id;
            foreach ($apiData as $apiModule) {
                if (substr($apiModule->name, 0, strlen($query)) === $query) {
                    $module->enabled = true;
                }
            }
            return $module;
        }, get_purgely_instance()->fastly_edge_modules());
    }

    protected function renderGroup($group, $values, $suffix)
    {
        $values = $values ? $values : [];
        $name = "{$suffix}[{$group->name}]";
        $suffix = "{$suffix}-{$group->name}";
        ?>
        <tr>
            <th rowspan="2">
                <label>
                    <?php echo __($group->label); ?>
                </label>
            </th>
            <td>
                <a href="#" title="Add group" class="button" onclick="return EdgeModules.addGroup('<?php echo $suffix; ?>', '<?php echo $name; ?>')">Add group</a>
            </td>
        </tr>
        <tr>
            <td>
                <?php for ($i = 0; $i < count($values); $i++): ?>
                    <?php $this->renderGroupProperties($group->properties, $values[$i], "{$name}[$i]", "{$suffix}-{$i}"); ?>
                <?php endfor; ?>
                <template id="<?php echo $suffix.'-template'; ?>">
                    <?php $this->renderGroupProperties($group->properties, [], "{$name}[x]", 'container'); ?>
                </template>
            </td>
        </tr>
        <?php
    }

    protected function renderGroupProperties($properties, $values, $name, $id)
    {
        ?>
        <div id="<?php echo $id; ?>">
            <table class="form-table">
                <tbody>
                <?php foreach($properties as $property): ?>
                    <?php $this->renderProperty($name, $property, $values[$property->name]); ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            <span class="submitbox">
                <a class="submitdelete" href="#" onclick="return EdgeModules.removeGroup(this)">Remove</a>
            </span>
            <hr/>
        </div>
        <?php
    }

    protected function renderProperty($name, $property, $value)
    {
        ?>
        <tr>
            <th>
                <label for="<?php echo $property->name; ?>">
                    <?php echo __($property->label); ?>
                </label>
            </th>
            <td>
                <?php echo $this->renderField($name, $property, $value); ?>
                <p><small><em><?php echo $property->description; ?></em></small></p>
            </td>
        </tr>
        <?php
    }

    protected function renderField($name, $property, $value = null)
    {
        $id = $property->name;
        $name = "{$name}[{$property->name}]";
        $value = !is_null($value) ? $value : $property->default;
        $required = $property->required ? 'required' : '';

        switch ($property->type) {
            case 'acl':
                return '<p>ACL</p>';
            case 'dict':
                return '<p>Dictionary</p>';
            case 'select':
                $options = '';
                foreach ((array) $property->options as $k => $label) {
                    $selected = $k === $value ? 'selected' : '';
                    $options .= "<option value='{$k}' {$selected}>{$label}</option>";
                };
                return "<select style='width: 100%;' id='{$id}' name='{$name}' {$required}>{$options}</select>";
            case 'boolean':
                $value = $value === 'true';
                $options = implode('', [
                    "<option value='false' ".(!$value ? 'selected' : '').">No</option>",
                    "<option value='true' ".($value ? 'selected' : '').">Yes</option>",
                ]);
                return "<select style='width: 100%;' id='{$id}' name='{$name}' {$required}>{$options}</select>";
            case 'integer':
            case 'float':
                $type = 'number';
                return "<input style='width: 100%;' type='{$type}' id='{$id}' name='{$name}' value='{$value}' {$required}/>";
            case 'string':
            case 'path':
            default:
                $type = 'text';
                return "<input style='width: 100%;' type='{$type}' id='{$id}' name='{$name}' value='{$value}' {$required}/>";
        }
    }

    public function processFormSubmission($data)
    {
        unset($data['nonce']);

        // upload to Fastly's API, continue if successful, else return message
        // each group should have a snippet key already parsed by Handlebars.js before form submission

        $currentData = get_option(self::SETTINGS, []);
        $data = array_merge($currentData , array_map(function ($d) {
            $d['uploaded_at'] = date(DATE_ISO8601);
            return $d;
        }, $data));
        update_option(self::SETTINGS, $data);
    }
}
