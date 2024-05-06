<?php

class Fastly_Edgemodules
{
    const SETTINGS = 'fastly-settings-edgemodules';

    const EDGE_PREFIX = 'edgemodule';

    private static $instance;

    protected $acls = null;

    protected $dictionaries = null;

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
                    <img alt="fastly" src="<?php echo esc_attr( FASTLY_PLUGIN_URL . 'static/logo_white.gif' ); ?>"><br>
                    <span style="font-size: x-small;">version: <?php echo esc_html( FASTLY_VERSION ); ?></span>
                </h1>
            </div>
            Fastly Edge Modules is a framework that allows you to enable specific functionality on Fastly without needing to write any VCL code.
	    Below is a list of functions you can enable. Some may have additional options you can configure. To enable or disable click
            on the <strong>Manage</strong> button next to the functionality you want to enable, configure any available options then click <strong>Upload</strong>.  
            To disable/remove the module click on <strong>Manage</strong> then click on <strong>Disable</strong>.
            <table class="form-table">
                <tbody>
                <?php foreach ($modules as $module): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $module->name ); ?></strong><br>
                            <p>
                                <em><?php echo esc_html( $module->description ); ?></em>
                            </p>
                        </td>
                        <td nowrap="nowrap">
                            <em>
                                <strong><?php echo (isset($module->enabled) && $module->enabled) ? esc_html__('Enabled', 'purgely') : esc_html__('Disabled', 'purgely'); ?></strong><br>
                                Uploaded: <?php echo isset($module->data['uploaded_at']) ? esc_html( gmdate('Y/m/d' , strtotime($module->data['uploaded_at'] ) ) ) : esc_html__('never', 'purgely'); ?>
                            </em>
                        </td>
                        <td nowrap="nowrap">
                            <a href="#TB_inline?&width=800&inlineId=fastly-edge-module-<?php echo esc_attr( $module->id ); ?>"
                               title="<?php echo esc_attr( $module->name ); ?>" class="button thickbox">Manage</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <span class="spinner" id="html-popup-spinner" style="position: absolute; top:0;left:0;width:100%;height:100%;margin:0; background-color: #fff; background-position:center;"></span>
        </div>
        <?php foreach ($modules as $module): ?>
        <div id="fastly-edge-module-<?php echo esc_attr( $module->id ); ?>" style="display:none;">
            <form action="<?php menu_page_url( 'fastly-edge-modules' ) ?>" onsubmit="return EdgeModules.submit(this)" method="post">
                <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce('fastly-edge-modules') ); ?>">
                <input type="hidden" id="<?php echo esc_attr( "$module->id-key" ); ?>" value='<?php echo esc_attr( $module->id ); ?>'>
                <input type="hidden" id="<?php echo esc_attr( "$module->id-vcl" ); ?>" value='<?php echo rawurlencode( wp_json_encode($module->vcl) ); ?>'>
                <input type="hidden" id="<?php echo esc_attr( "$module->id-snippet" ); ?>" name="<?php echo esc_attr( "$module->id[snippet]" ); ?>">
                <table class="form-table">
                    <tbody>
                    <?php if( !empty($module->properties) ): ?>
                        <?php foreach($module->properties as $property): ?>
                            <?php if($property->type === 'group'): ?>
                                <?php $this->renderGroup($property, (isset($module->data[$property->name])) ? $module->data[$property->name] : null, $module->id); ?>
                            <?php else: ?>
                                <?php $this->renderProperty($module->id, $property, (isset($module->data[$property->name])) ? $module->data[$property->name] : null); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>
                                <span class="submitbox">
                                    <a class="submitdelete" href="#" onclick="return EdgeModules.disableModule('<?php echo esc_attr( $module->id ); ?>')">Disable</a>
                                </span>
                            </td>
                            <td>
                                <input type="submit" value="Upload" class="button button-primary" style="float: right">
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="<?php echo esc_attr( "$module->id-disable-form" ); ?>" method="post" style="display: none;">
                <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce('fastly-edge-modules-disable') ); ?>">
                <input type="hidden" name="action" value="fastly_module_disable_form">
                <input type="hidden" name="module_name" value='<?php echo esc_attr( $module->id ); ?>'>
                <?php for ($i = 0; $i < count($module->vcl); $i++): ?>
                    <input type="hidden" name="types[<?php echo esc_attr( $i ); ?>]" value='<?php echo esc_attr( $module->vcl[$i]->type ); ?>'>
                <?php endfor; ?>
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
            $module->data = $localData[$module->id] ?? [];
            $query = self::EDGE_PREFIX.'_'.$module->id;
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
                    <?php echo esc_html( $group->label ); ?>
                </label>
            </th>
            <td>
                <a href="#" title="Add group" class="button" onclick="return EdgeModules.addGroup('<?php echo esc_attr( $suffix ); ?>', '<?php echo esc_attr( $name ); ?>')">Add group</a>
            </td>
        </tr>
        <tr>
            <td>
                <?php foreach ($values as $key => $value): ?>
                    <?php $this->renderGroupProperties($group->properties, $value, "{$name}[$key]", "{$suffix}-{$key}"); ?>
                <?php endforeach; ?>
                <template id="<?php echo esc_attr( "$suffix-template" ); ?>">
                    <?php $this->renderGroupProperties($group->properties, [], "{$name}[x]", 'container'); ?>
                </template>
            </td>
        </tr>
        <?php
    }

    protected function renderGroupProperties($properties, $values, $name, $id)
    {
        ?>
        <div id="<?php echo esc_attr( $id ); ?>">
            <table class="form-table">
                <tbody>
                <?php foreach($properties as $property): ?>
                    <?php $this->renderProperty($name, $property, $values[$property->name] ?? ""); ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            <span class="submitbox">
                <a class="submitdelete" href="#" onclick="return EdgeModules.removeGroup(this)">Remove group</a>
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
                <label for="<?php echo esc_attr( $property->name ); ?>">
                    <?php echo esc_html( $property->label ); ?>
                </label>
            </th>
            <td>
                <?php $this->renderField($name, $property, $value); ?>
                <p><small><em><?php echo esc_html( $property->description ?? "" ); ?></em></small></p>
            </td>
        </tr>
        <?php
    }

    protected function renderField($name, $property, $value = null)
    {
        $id = $property->name;
        $name = "{$name}[{$property->name}]";

        if(!$value && isset($property->default)){
            $value = $property->default;
        }


        $required = $property->required ? 'required' : '';

        switch ($property->type) {
            case 'acl':
                echo "<select style='width: 100%;' id=' " . esc_attr($id) . "' name='" . esc_attr($name) . "' " . esc_attr($required) . ">";
                foreach ($this->getAcls() as $acl) {
                    $selected = $acl->name === $value ? 'selected' : '';
                    echo "<option value='" . esc_attr($acl->name) . "' " . esc_attr($selected) . ">" . esc_html($acl->name) . "</option>";
                }
                echo "</select>";
                break;
            case 'dict':
                echo "<select style='width: 100%;' id=' " . esc_attr($id) . "' name='" . esc_attr($name) . "' " . esc_attr($required) . ">";
                foreach ($this->getDictionaries() as $dictionary) {
                    $selected = $dictionary->name === $value ? 'selected' : '';
                    echo "<option value='" . esc_attr($dictionary->name) . "' " . esc_attr($selected) . ">" . esc_html($dictionary->name) . "</option>";
                }
                echo "</select>";
                break;
            case 'select':
                echo "<select style='width: 100%;' id=' " . esc_attr($id) . "' name='" . esc_attr($name) . "' " . esc_attr($required) . ">";
                foreach ((array) $property->options as $k => $label) {
                    $selected = $k === $value ? 'selected' : '';
                    echo "<option value='" . esc_attr($k). "' " . esc_attr($selected) . ">" . esc_html($label) . "</option>";

                }
                echo "</select>";
                break;
            case 'boolean':
                echo "<select style='width: 100%;' id=' " . esc_attr($id) . "' name='" . esc_attr($name) . "' " . esc_attr($required) . ">";

                echo"<option value='0' ".(!$value ? 'selected' : '').">No</option>" .
                    "<option value='1' ".($value ? 'selected' : '').">Yes</option>";
                echo "</select>";
                break;
            case 'integer':
            case 'float':
                echo "<input style='width: 100%;' type='number' id='" . esc_attr($id) . "' name='" . esc_attr($name) .
                    "' value='" . esc_attr($value) . "'" .  esc_attr($required) . "/>";
                break;
            case 'string':
            case 'path':
            default:

            echo "<input style='width: 100%;' type='text' id='" . esc_attr($id) . "' name='" . esc_attr($name) .
                "' value='" . esc_attr($value) . "'" .  esc_attr($required) . "/>";
                break;
        }
    }

    protected function getAcls()
    {
        if (is_null($this->acls)) {
            $this->acls = fastly_api()->get_all_acls();
        }
        return $this->acls;
    }

    protected function getDictionaries()
    {
        if (is_null($this->dictionaries)) {
            $this->dictionaries = fastly_api()->get_all_dictionaries();
        }
        return $this->dictionaries;
    }

    public function processFormSubmission($data)
    {
        unset($data['nonce']);

        $clone = fastly_api()->clone_active_version();
        foreach ($data as $key => $datum) {
            $snippets = json_decode(rawurldecode($datum['snippet']));
            foreach ($snippets as $snippet) {
                $success = fastly_api()->upload_snippet($clone->number, [
                    'name'      => self::EDGE_PREFIX.'_'.$key.'_'.$snippet->type,
                    'type'      => $snippet->type,
                    'dynamic'   => "0",
                    'priority'  => $snippet->priority,
                    'content'   => $snippet->snippet
                ]);
                if (!$success) {
                    return;
                }
            }
        }
        if (!fastly_api()->validate_version($clone->number)) {
            return;
        }
        fastly_api()->activate_version($clone->number);

        $currentData = get_option(self::SETTINGS, []);
        $data = array_merge($currentData , array_map(function ($d) {
            unset($d['snippet']);
            $d['uploaded_at'] = gmdate(DATE_ISO8601);
            return $d;
        }, $data));
        update_option(self::SETTINGS, $data);
    }

    public function processFormSubmissionDisable($data)
    {
        $clone = fastly_api()->clone_active_version();
        foreach ($data['types'] as $type) {
            $name = self::EDGE_PREFIX.'_'.$data['module_name'].'_'.$type;
            if (!fastly_api()->delete_snippet($clone->number, $name)) {
                return;
            }
        }

        if (!fastly_api()->validate_version($clone->number)) {
            return;
        }
        fastly_api()->activate_version($clone->number);

        $currentData = get_option(self::SETTINGS, []);
        unset($currentData[$data['module_name']]);
        update_option(self::SETTINGS, $currentData);
    }
}
