<?php namespace Mosaicpro\WpCore;

use Mosaicpro\Alert\Alert;
use Mosaicpro\Button\Button;
use Mosaicpro\ButtonGroup\ButtonGroup;
use Mosaicpro\ListGroup\ListGroup;
use Mosaicpro\Table\Table;

/**
 * Class CRUD
 * @package Mosaicpro\WpCore
 */
class CRUD
{
    /**
     * Holds the post prefix
     * @var
     */
    protected $prefix;

    /**
     * Holds the main post type
     * @var
     */
    protected $post;

    /**
     * Holds the Related post type
     * @var
     */
    protected $related;

    /**
     * Holds the Related prefix
     * @var
     */
    protected $related_prefix;

    /**
     * Holds the fields used for composing the Related List table columns
     * @var
     */
    protected $list_fields = ['default' => ['ID', 'post_title']];

    /**
     * Holds the fields used for composing the Post Related List table columns
     * @var array
     */
    protected $post_related_list_fields = ['default' => ['ID', 'post_title']];

    /**
     * Holds what actions will be displayed in the Related List table
     * @var array
     */
    protected $list_actions = ['default' => ['edit_related', 'add_to_post']];

    /**
     * Holds the post related list actions / buttons
     * @var array
     */
    protected $post_related_list_actions = ['default' => ['edit_related_thickbox', 'remove_from_post']];

    /**
     * Holds the edit form buttons
     * @var array
     */
    protected $form_buttons = ['default' => ['save', 'full_edit']];

    /**
     * Holds the edit form fields
     * @var array
     */
    protected $form_fields = ['default' => ['post_title']];

    /**
     * Holds form validation callbacks
     * @var array
     */
    protected $form_validation = [];

    /**
     * Holds the latest form validation error
     * @var
     */
    protected $form_validation_error;

    /**
     * Holds custom query arguments for fetching the Related List
     * @var array
     */
    protected $list_query = [];

    /**
     * Holds the related list format
     * @var string
     */
    protected $list_related_format = 'table';

    /**
     * Holds the post related list format
     * @var string
     */
    protected $list_post_related_format = 'table';

    /**
     * Holds the CRUD instance ID
     * @var string
     */
    protected $instance;

    /**
     * Holds whether CRUD is mixed / contains multiple related post types
     * @var bool
     */
    protected $mixed;

    /**
     * @return mixed|string
     */
    private function getRelatedId()
    {
        return is_array($this->getRelated()) ? implode('_', $this->getRelated()) : $this->getRelated();
    }

    /**
     * @param $related
     * @param $prefix
     * @return $this
     */
    private function setRelatedPrefix($related, $prefix)
    {
        return $this->setComponents('related_prefix', $related, $prefix);
    }

    /**
     * Create a new CRUD instance
     * @param $prefix
     * @param $post
     * @param $related
     */
    public function __construct($prefix, $post, $related)
    {
        $this->post = $post;
        $this->prefix = $prefix;
        $this->mixed = is_array($related);

        $this->setRelated($related);
        $this->setInstance();
        return $this;
    }

    /**
     * Set the instance identifier
     */
    private function setInstance()
    {
        if ($this->mixed) $this->instance = 'crud_related_instance_mixed_' . $this->getRelatedId();
        else $this->instance = 'crud_related_instance_' . $this->getRelated();
    }

    /**
     * Sets the Related options
     * @param $related
     */
    private function setRelated($related)
    {
        if ($this->mixed)
        {
            foreach ($related as $related_item)
            {
                if (is_array($related_item))
                    $this->setRelatedDefaults($related_item[1], $related_item[0], true);
                else
                    $this->setRelatedDefaults($related_item, $this->prefix, true);
            }
        }
        else
            $this->setRelatedDefaults($related, $this->prefix);
    }

    /**
     * @param $related
     * @param $prefix
     * @param bool $array
     */
    private function setRelatedDefaults($related, $prefix, $array = false)
    {
        $this->setRelatedPrefix($related, $prefix);
        $this->setListFields($prefix . '_' . $related, $this->list_fields['default']);
        $this->setListActions($prefix . '_' . $related, $this->list_actions['default']);
        $this->setPostRelatedListActions($prefix . '_' . $related, $this->post_related_list_actions['default']);

        if ($array) $this->related[] = $related;
        else $this->related = $related;
    }

    /**
     * Get an array of the Related post types
     * @return array
     */
    private function getRelatedPostTypes()
    {
        $types = $this->getRelatedType();
        if (!is_array($types)) $types = [$types];
        return $types;
    }

    /**
     * Get an array of all post types
     * @return array
     */
    private function getPostTypes()
    {
        $types = $this->getRelatedPostTypes();
        array_unshift($types, $this->prefix . '_' . $this->post);
        return $types;
    }

    /**
     * Set the fields used for composing the Related List table columns;
     * By default, the Post Related List (Related Meta Box) will copy this format;
     * @param $related
     * @param $fields
     * @return $this
     */
    public function setListFields($related, $fields)
    {
        $this->setComponents('list_fields', $related, $fields);
        $this->setComponents('post_related_list_fields', $related, $fields);
        return $this;
    }

    /**
     * Returns the stored list_fields
     * @return mixed
     */
    public function getListFields()
    {
        return $this->list_fields;
    }

    /**
     * Returns the stored instance
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Returns the stored related
     * @return mixed
     */
    public function getRelated()
    {
        return $this->related;
    }

    /**
     * Get an array of all registered Related short names (without prefix)
     * @return array|mixed
     */
    private function getRelatedList()
    {
        $related_list = [$this->getRelated()];
        if ($this->mixed) $related_list = $this->getRelated();
        return $related_list;
    }

    /**
     * Set the post related list fields
     * Can also accept a $fields closure that allows to copy the list_fields
     * e.g. setPostRelatedListFields('my_post_type', function($instance){
     *      return $instance->getListFields();
     * });
     * @param $related
     * @param $fields
     * @return $this
     */
    public function setPostRelatedListFields($related, $fields)
    {
        return $this->setComponents('post_related_list_fields', $related, $fields);
    }

    /**
     * Set the actions to be displayed in the Related List table
     * @param $related
     * @param $actions
     * @return $this
     */
    public function setListActions($related, $actions)
    {
        return $this->setComponents('list_actions', $related, $actions);
    }

    /**
     * Set custom query arguments for fetching the Related List
     * @param $query
     * @return $this
     */
    public function setListQuery($query)
    {
        $this->list_query = $query;
        return $this;
    }

    /**
     * @param string $list_related_format
     * @return $this
     */
    public function setListRelatedFormat($list_related_format)
    {
        $this->list_related_format = $list_related_format;
        return $this;
    }

    /**
     * @param string $list_post_related_format
     * @return $this
     */
    public function setListPostRelatedFormat($list_post_related_format)
    {
        $this->list_post_related_format = $list_post_related_format;
        return $this;
    }

    /**
     * @param $related
     * @param array $actions
     * @return $this
     */
    public function setPostRelatedListActions($related, $actions)
    {
        return $this->setComponents('post_related_list_actions', $related, $actions);
    }

    /**
     * Hook into the Edit Related Form
     * @param $related
     * @param $callback
     * @return $this
     */
    public function setForm($related, $callback)
    {
        add_action('crud_' . $this->prefix . '_edit_' . $related . '_form', $callback);
        return $this;
    }

    /**
     * Initialize the Form buttons
     */
    private function initFormButtons()
    {
        $post_types = $this->getRelatedType();
        if (!is_array($post_types)) $post_types = [$post_types];

        foreach($post_types as $post_type)
        {
            $buttons = isset($this->form_buttons[$post_type]) ? $this->form_buttons[$post_type] : $this->form_buttons['default'];
            foreach($buttons as $button_id)
            {
                if (is_callable($button_id))
                    add_action('crud_' . $this->prefix . '_edit_' . $post_type . '_form_buttons', $button_id);
                else
                    add_action('crud_' . $this->prefix . '_edit_' . $post_type . '_form_buttons', [$this, 'getFormButton_' . $button_id]);
            }
        }
    }

    /**
     * Sets the Form Buttons
     * @param $post_type
     * @param $buttons
     * @return $this
     */
    public function setFormButtons($post_type, $buttons)
    {
        return $this->setComponents('form_buttons', $post_type, $buttons);
    }

    /**
     * Form Save Button
     * @param $post
     */
    public function getFormButton_save($post)
    {
        echo Button::success('Save')->isSubmit()->pullRight();
    }

    /**
     * Form Full Edit Button
     * @param $post
     */
    public function getFormButton_full_edit($post)
    {
        echo Button::link('Go to full edit page')->addUrl(get_edit_post_link($post->ID))->addAttributes(['target' => '_parent'])->pullRight();
    }

    /**
     * Initialize the Form Fields
     */
    private function initFormFields()
    {
        $post_types = $this->getPostTypes();
        foreach($post_types as $post_type)
        {
            $fields = isset($this->form_fields[$post_type]) ? $this->form_fields[$post_type] : $this->form_fields['default'];
            foreach($fields as $field_id)
            {
                $action = 'crud_' . $this->prefix . '_edit_' . $post_type . '_form_fields';
                if (has_action($action)) continue;
                if (is_callable($field_id)) add_action($action, $field_id);
                else add_action($action, [$this, 'getFormField_' . $field_id]);
            }
        }
    }

    /**
     * Set a form validation callback
     * @param $form
     * @param $callback
     * @return $this
     */
    public function validateForm($form, $callback)
    {
        return $this->setComponents('form_validation', $form, $callback);
    }

    /**
     * Returns whether the form passes validation
     * @param $form
     * @return bool
     */
    private function formPassesValidation($form)
    {
        $validation = empty($this->form_validation[$form]) ? true : $this->form_validation[$form];
        return is_callable($validation) ? $validation($this) : $validation;
    }

    /**
     * Set the form validation error
     * @param string $message
     * @return bool
     */
    public function setFormValidationError($message = 'Form Validation Error')
    {
        $this->form_validation_error = $message;
        return false;
    }

    /**
     * Get the latest form validation error
     * @return mixed
     */
    private function getFormValidationError()
    {
        return $this->form_validation_error;
    }

    /**
     * @param $post_type
     * @param $fields
     * @return $this
     */
    public function setFormFields($post_type, $fields)
    {
        return $this->setComponents('form_fields', $post_type, $fields);
    }

    /**
     * Predefined post_title Form Field
     * @param $post
     */
    public function getFormField_post_title($post)
    {
        FormBuilder::input('post_title', 'Title', esc_attr($post->post_title));
    }

    /**
     * Sets the $component for $post_type as $args
     * @param string $component
     * @param $post_type
     * @param $args
     * @return $this
     */
    private function setComponents($component = '', $post_type, $args)
    {
        $this->{$component}[$post_type] = $args;
        // $this->{$component}[$post_type] = is_callable($args) ? $args($this) : $args;
        return $this;
    }

    /**
     * Return the Related post type with or without the Related prefix
     * @param null $related
     * @return string
     */
    private function getRelatedType($related = null)
    {
        if (is_null($related)) $related = $this->getRelated();
        if ($this->mixed)
        {
            if (!is_array($related)) $related = [$related];

            $related_type_list = [];
            foreach ($related as $related_item)
            {
                $related_prefix = '';
                if (!empty($this->related_prefix[$related_item])) $related_prefix = $this->related_prefix[$related_item] . '_';
                $related_type = $related_prefix . $related_item;
                $related_type_list[] = $related_type;
            }
            return $related_type_list;
        }
        else
        {
            $related_prefix = '';
            if (!empty($this->related_prefix[$related])) $related_prefix = $this->related_prefix[$related] . '_';
            $related_type = $related_prefix . $related;
        }

        return $related_type;
    }

    /**
     * Apply filter for post type name label
     * @param $related_item
     * @return mixed|void
     */
    public static function getPostTypeLabel($related_item)
    {
        return apply_filters('crud_post_type_label_' . $related_item, $related_item);
    }

    /**
     * Add filter for post type name label
     * @param $post_type
     * @param $label
     */
    public static function setPostTypeLabel($post_type, $label)
    {
        add_filter('crud_post_type_label_' . $post_type, function() use ($label)
        {
            return $label;
        });
    }

    /**
     * Get the Post ID on post.php and post-new.php pages
     * @return bool|int
     */
    public static function getPostID()
    {
        $post_id = false;
        if( isset($_GET['post']) ) $post_id = absint($_GET['post']);
        elseif( isset($_POST['post_ID']) ) $post_id = absint($_POST['post_ID']);
        return $post_id;
    }

    /**
     * Initialize CRUD
     * @return $this
     */
    public function register()
    {
        $this->initFormFields();
        $this->initFormButtons();
        $this->register_scripts();
        $this->handle_ajax_edit_related();
        $this->handle_ajax_list_related();
        $this->handle_ajax_list_post_related();
        $this->handle_ajax_list_post_related_mixed();
        $this->handle_ajax_add_post_related();
        $this->handle_ajax_remove_post_related();
        return $this;
    }

    /**
     * Registers the required scripts in wp admin add/edit pages
     */
    private function register_scripts()
    {
        add_action('admin_enqueue_scripts', function($hook)
        {
            global $post_type;
            global $post;
            if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === $this->prefix . '_' . $this->post)
            {
                $script_id = 'crud_related';
                wp_enqueue_script($script_id, plugin_dir_url(__FILE__) . 'js/crud/related.js', ['jquery'], '1.0', true);
                wp_localize_script(
                    $script_id,
                    $this->getInstance(),
                    array(
                        'nonce' => wp_create_nonce( $this->prefix . "_" . $this->post . "_nonce" ),
                        'post_id' => $post->ID,
                        'prefix' => $this->prefix,
                        'post' => $this->post,
                        'related' => $this->related,
                    )
                );
            }
        });
    }

    /**
     * Handle Related List AJAX requests
     */
    private function handle_ajax_list_related()
    {
        $related_list = $this->getRelatedList();
        foreach($related_list as $related)
        {
            add_action('wp_ajax_' . $this->prefix . '_list_' . $related, function($related) use ($related)
            {
                wp_enqueue_script('ajax_list_' . $related, plugin_dir_url(__FILE__) . 'js/crud/ajax_list.js', ['jquery'], '1.0', true);
                ThickBox::getHeader();

                $related_posts_query = [
                    'post_type' => $this->getRelatedType($related),
                    'numberposts' => -1
                ];
                $related_posts_query = array_merge($related_posts_query, $this->list_query);
                $related_posts = get_posts($related_posts_query);

                if (count($related_posts) > 0)
                    echo $this->get_list_format($this->list_related_format, $related_posts);
                else
                    echo Alert::make()->addAlert('No related posts found (' . $this->getRelatedType($related) . ').')->isInfo();

                ThickBox::getFooter();
                die();
            });
        }
    }

    /**
     * Handle Edit Related AJAX requests
     */
    private function handle_ajax_edit_related()
    {
        $related_list = $this->getRelatedPostTypes();
        foreach($related_list as $related_item)
        {
            add_action('wp_ajax_' . $this->prefix . '_edit_' . $related_item, function($related_item) use ($related_item)
            {
                $related_id = !empty($_REQUEST['related_id']) ? $_REQUEST['related_id'] : false;
                $is_post = !empty($_POST);

                if ($is_post)
                {
                    check_ajax_referer( $this->prefix . '_' . $related_item . '_nonce', 'nonce' );
                    if ( false ) wp_send_json_error( 'Security error' );
                    if ( !$this->formPassesValidation($related_item) ) wp_send_json_error( $this->getFormValidationError() );

                    $related_save = $_POST;
                    $related_save['post_status'] = 'publish';
                    $related_save['post_type'] = $related_item;

                    unset($related_save['nonce']);
                    unset($related_save['action']);

                    if ($related_id) $related_save['ID'] = $related_id;
                    unset($related_save['related_id']);

                    if (isset($related_save['meta']))
                    {
                        $save_meta_fields = array_keys($related_save['meta']);
                        $save_meta_fields = array_map(function($value){ return 'meta[' . $value . ']'; }, $save_meta_fields);
                        PostData::save_meta_fields($save_meta_fields);
                        unset($related_save['meta']);
                    }

                    if ($related_id) $saved = wp_update_post($related_save, true);
                    else {
                        $supports = post_type_supports($related_item, 'title');
                        if (!$supports) PostData::allow_empty();
                        $saved = wp_insert_post($related_save, true);
                    }

                    if (is_a($saved, 'WP_Error')) wp_send_json_error($saved->get_error_messages());
                    wp_send_json_success();
                    die();
                }

                $related = get_post($related_id);

                wp_enqueue_script('ajax_edit_related', plugin_dir_url(__FILE__) . 'js/crud/ajax_edit_related.js', ['jquery'], '1.0', true);
                wp_localize_script(
                    'ajax_edit_related',
                    'related_data',
                    array(
                        'nonce' => wp_create_nonce( $this->prefix . "_" . $related_item . "_nonce" ),
                        'action' => $this->prefix . '_edit_' . $related_item,
                        'related_id' => $related_id
                    )
                );

                ThickBox::getHeader();
                ?>
                <div class="col-md-12">
                    <h3>Edit <?php echo $this->getPostTypeLabel($related_item); ?></h3>
                </div>
                <hr/>
                <form action="" class="edit-related-form" data-related-instance="<?php echo $this->getInstance(); ?>" method="post">
                    <div class="col-md-12">
                        <?php
                        do_action('crud_' . $this->prefix . '_edit_' . $related_item . '_form_fields');
                        do_action('crud_' . $this->prefix . '_edit_' . $related_item . '_form', $related, $this);
                        do_action('crud_' . $this->prefix . '_edit_' . $related_item . '_form_buttons', $related);
                        ?>
                    </div>
                </form>
                <?php
                ThickBox::getFooter();
                die();
            });
        }
    }

    /**
     * Handle List Post Related AJAX requests
     */
    private function handle_ajax_list_post_related()
    {
        if (is_array($this->getRelated())) return false;
        add_action('wp_ajax_' . $this->prefix . '_list_' . $this->post . '_' . $this->getRelated(), function()
        {
            check_ajax_referer( $this->prefix . '_' . $this->post . '_nonce', 'nonce' );
            if ( false ) wp_send_json_error( 'Security error' );

            $post_id = $_POST['post_id'];
            $related_key = $this->prefix . '_' . $this->getRelated();

            $list = get_post_meta($post_id, $related_key, true);
            if (!is_array($list) || empty($list)) $list = [];

            $list_ids = [];
            foreach($list as $list_item) $list_ids[] = $list_item['id'];
            if (empty($list_ids)) return wp_send_json_success();

            $related_posts = get_posts([
                'post_type' => $this->getRelatedType(),
                'numberposts' => -1,
                'post__in' => $list_ids
            ]);

            $list_format = $this->get_list_format($this->list_post_related_format, $related_posts, 'post_related_');
            wp_send_json_success( $list_format );
        });
    }

    /**
     * Handle List Post Related Mixed AJAX requests
     */
    private function handle_ajax_list_post_related_mixed()
    {
        if (!is_array($this->getRelated())) return false;
        add_action('wp_ajax_' . $this->prefix . '_list_' . $this->post . '_' . $this->getRelatedId(), function()
        {
            check_ajax_referer( $this->prefix . '_' . $this->post . '_nonce', 'nonce' );
            if ( false ) wp_send_json_error( 'Security error' );

            $post_id = $_POST['post_id'];
            $related_key = $this->prefix . '_mixed';

            $list = get_post_meta($post_id, $related_key, true);
            if (!is_array($list) || empty($list)) $list = [];

            $list_ids = [];
            foreach($list as $list_item) $list_ids[] = $list_item['id'];
            $list_ids = array_unique($list_ids);
            if (empty($list_ids)) return wp_send_json_success();

            $related_posts = get_posts([
                'post_type' => $this->getRelatedType(),
                'numberposts' => -1,
                'post__in' => $list_ids
            ]);

            $list_format = $this->get_list_format($this->list_post_related_format, $related_posts, 'post_related_');
            wp_send_json_success( $list_format );
        });
    }

    /**
     * Forwards a posts list to the right format method
     * @param $format_key
     * @param $related_posts
     * @param string $prefix
     * @return mixed
     */
    private function get_list_format($format_key, $related_posts, $prefix = '')
    {
        if (is_callable($format_key)) return $format_key($related_posts);
        return $this->{"get_list_format_" . $format_key}($related_posts, $prefix);
    }

    /**
     * Formats a list row data for output
     * @param $related_post
     * @param $fields
     * @return array
     */
    private function get_list_format_row($related_post, $fields)
    {
        $related_table_row = [];
        foreach ($fields as $field => $value)
        {
            if (is_numeric($field)) $field = $value;
            $field_label = !is_callable($field) ? ucwords(str_replace("_", " ", $field)) : '';
            if (is_callable($field))
            {
                $callable = $field($related_post);
                $related_table_row[$callable['field']] = $callable['value'];
            }
            elseif ($field == 'post_title_permalink')
            {
                $related_table_row['Title'] = \Mosaicpro\Core\IoC::getContainer('html')
                        ->link(get_permalink($related_post->ID), $related_post->post_title) .
                    '<p>' . wp_trim_words(strip_tags($related_post->post_content)) . '</p>';
            }
            elseif ($field == 'post_thumbnail')
                $related_table_row['Image'] = PostList::post_thumbnail($related_post->ID, 50, 50);
            elseif (starts_with($field, 'crud_edit_'))
            {
                $parts = explode('crud_edit_', $field);
                $field_label = ucwords(str_replace("_", " ", $parts[1]));
                $field = $parts[1];
                $related_table_row[$field_label] = Button::link($related_post->{$field})
                    ->isLink()
                    ->addClass('thickbox')
                    ->addAttributes(['title' => 'Edit ' . CRUD::getPostTypeLabel($related_post->post_type)])
                    ->addUrl(admin_url() . 'admin-ajax.php?action=' . $this->prefix . '_edit_' . $related_post->post_type . '&related_id=' . $related_post->ID . '#TB_iframe?width=600&width=550');
            }
            elseif (starts_with($field, 'count_'))
            {
                $parts = explode('count_', $field);
                $field = $parts[1];
                $field_label = self::getPostTypeLabel($field) . '(s)';
                $related_table_row[$field_label] = count($related_post->{$field}) . ' ' . $field_label;
            }
            elseif (starts_with($field, 'yes_no_'))
            {
                $parts = explode('yes_no_', $field);
                $field_label = ucwords(str_replace("_", " ", $parts[1]));
                $field = $parts[1];
                $related_table_row[$field_label] = $related_post->{$field} == 1 ? '<strong>Yes</strong>' : 'No';
            }
            elseif (isset($related_post->{$field}))
                $related_table_row[$field_label] = $related_post->{$field};
            else
                $related_table_row[$field_label] = '';
        }
        return $related_table_row;
    }

    /**
     * Format a post list with the ListGroup component
     * @param $related_posts
     * @param string $prefix
     * @return mixed
     */
    private function get_list_format_listgroup($related_posts, $prefix = '')
    {
        $list_wrapper = ListGroup::make();
        foreach($related_posts as $related_post)
        {
            $actions = $this->get_list_actions($prefix . 'list_actions', $related_post);
            $button_group = ButtonGroup::make()->isXs()->pullRight();
            foreach ($actions as $action)
                $button_group->add($action);

            $row = $this->get_list_format_row($related_post, $this->{$prefix . 'list_fields'}[$related_post->post_type]);

            $row_output = '';
            foreach($row as $row_heading => $row_content)
                $row_output .= '<p><strong>' . $row_heading . '</strong>: ' . $row_content . '</p>';

            $list_content = $button_group . $row_output;
            $list_wrapper->addList($list_content);
        }
        return $list_wrapper->__toString();
    }

    /**
     * Format a post list with the Table component
     * @param $related_posts
     * @param string $prefix
     * @return mixed
     */
    private function get_list_format_table($related_posts, $prefix = '')
    {
        $related_table = [];
        $related_table_row_columns = [];
        foreach ($related_posts as $related_post)
        {
            $row = $this->get_list_format_row($related_post, $this->{$prefix . 'list_fields'}[$related_post->post_type]);
            $actions = $this->get_list_actions($prefix . 'list_actions', $related_post);
            $button_group = ButtonGroup::make()->addAttributes(['class' => 'btn-group-xs'])->pullRight();
            foreach ($actions as $action)
                $button_group->add($action);

            $row['Actions'] = $button_group;
            $related_table[] = $row;
        }

        $tableHeader = $this->mixed && $prefix == 'post_related_' ? false : array_keys($related_table[0]);

        return Table::make()
            ->isStriped()
            ->addHeader($tableHeader, ['Actions' => ['class' => 'text-right']])
            ->addBody($related_table, ['Actions' => ['class' => 'text-right']])->__toString();
    }

    /**
     * Compose the list actions / buttons
     * @param $list_key
     * @param $related_post
     * @return array
     */
    private function get_list_actions($list_key, $related_post)
    {
        $actions = [];
        foreach($this->{$list_key}[$related_post->post_type] as $action)
        {
            if ($action == 'edit_related_thickbox')
            {
                $actions[] = Button::regular('<i class="glyphicon glyphicon-pencil"></i>')
                    ->addAttributes(['title' => 'Edit ' . self::getPostTypeLabel($related_post->post_type), 'class' => 'thickbox'])
                    ->addUrl(admin_url() . 'admin-ajax.php?action=' . $this->prefix . '_edit_' . $related_post->post_type . '&related_id=' . $related_post->ID . '#TB_iframe?width=600&width=550');
            }
            if ($action == 'edit_related')
            {
                $actions[] = Button::regular('<i class="glyphicon glyphicon-pencil"></i>')
                    ->addAttributes(['title' => 'Edit ' . self::getPostTypeLabel($related_post->post_type)])
                    ->addUrl(admin_url() . 'admin-ajax.php?action=' . $this->prefix . '_edit_' . $related_post->post_type . '&related_id=' . $related_post->ID . '#TB_iframe?width=600&width=550');
            }
            if ($action == 'add_to_post')
            {
                $actions[] = Button::success('<i class="glyphicon glyphicon-plus"></i>')
                    ->isXs()
                    ->addAttributes([
                        'data-toggle' => 'add-to-post',
                        'data-related-id' => $related_post->ID,
                        'data-related-title' => $related_post->post_title,
                        'data-related-type' => $related_post->post_type,
                        'data-related-instance' => $this->getInstance(),
                        'title' => 'Add ' . self::getPostTypeLabel($related_post->post_type) . ' to ' . $this->post
                    ]);
            }
            if ($action == 'remove_from_post')
            {
                $actions[] = Button::danger('<i class="glyphicon glyphicon-trash"></i>')
                    ->addAttributes([
                        'title' => 'Remove ' . self::getPostTypeLabel($related_post->post_type) . ' from ' . $this->post,
                        'data-toggle' => 'remove-from-post',
                        'data-related-id' => $related_post->ID,
                        'data-related-instance' => $this->getInstance()
                    ]);
            }
            if (is_callable($action))
                $actions[] = $action($related_post);
        }
        return $actions;
    }

    /**
     * Handle Add Post Related AJAX requests
     */
    private function handle_ajax_add_post_related()
    {
        add_action('wp_ajax_' . $this->prefix . '_add_' . $this->post . '_' . $this->getRelatedId(), function()
        {
            check_ajax_referer( $this->prefix . '_' . $this->post . '_nonce', 'nonce' );
            if ( false ) wp_send_json_error( 'Security error' );

            $post_id = $_POST['post_id'];
            $related = $_POST['related'];
            $related_key = $this->prefix . '_' . (is_array($this->getRelated()) ? 'mixed' : $this->getRelatedId());

            $list = get_post_meta($post_id, $related_key, true);
            if (!is_array($list)) $list = [];

            $list = array_add($list, $related['id'], $related);
            update_post_meta($post_id, $related_key, $list);

            wp_send_json_success( $list );
        });
    }

    /**
     * Handle Remove Post Related AJAX requests
     */
    private function handle_ajax_remove_post_related()
    {
        add_action('wp_ajax_' . $this->prefix . '_remove_' . $this->post . '_' . $this->getRelatedId(), function()
        {
            check_ajax_referer( $this->prefix . '_' . $this->post . '_nonce', 'nonce' );
            if ( false ) wp_send_json_error( 'Security error' );

            $post_id = $_POST['post_id'];
            $related_id = $_POST['related_id'];
            $related_key = $this->prefix . '_' . (is_array($this->getRelated()) ? 'mixed' : $this->getRelatedId());

            $list = get_post_meta($post_id, $related_key, true);
            if (!is_array($list)) $list = [];

            $list = array_except($list, [$related_id]);
            update_post_meta($post_id, $related_key, $list);

            wp_send_json_success( $list );
        });
    }

    /**
     * Create a new static CRUD instance
     * @param $prefix
     * @param $post
     * @param $related
     * @return static
     */
    public static function make($prefix, $post, $related)
    {
        return new static($prefix, $post, $related);
    }
} 