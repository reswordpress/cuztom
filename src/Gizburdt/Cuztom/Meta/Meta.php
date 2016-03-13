<?php

namespace Gizburdt\Cuztom\Meta;

use Gizburdt\Cuztom\Cuztom;
use Gizburdt\Cuztom\Support\Guard;
use Gizburdt\Cuztom\Fields\Field;
use Gizburdt\Cuztom\Fields\Bundle;
use Gizburdt\Cuztom\Fields\Tabs;
use Gizburdt\Cuztom\Fields\Accordion;

Guard::directAccess();

abstract class Meta
{
    /**
     * ID
     * @var string
     */
    public $id;

    /**
     * Object
     * @var integer
     */
    public $object;

    /**
     * Callback
     * @var string
     */
    public $callback;

    /**
     * Title
     * @var string
     */
    public $title;

    /**
     * Description
     * @var string
     */
    public $description;

    /**
     * Fields
     * @var array
     */
    public $fields;

    /**
     * Data
     * @var array
     */
    public $data;

    /**
     * Get object id
     *
     * @return int
     */
    abstract public function get_object_id();

    /**
     * Get meta values
     *
     * @return array
     */
    abstract public function get_meta_values();

    /**
     * Construct for all meta types, creates title (and description)
     *
     * @param int   $id   Object id
     * @param array $data Array of fields
     * @since 1.6.4
     */
    public function __construct($id, $data)
    {
        $properties = array_keys(get_class_vars(get_called_class()));

        // Set all properties
        foreach ($properties as $property) {
            $this->$property = isset($data[$property]) ? $data[$property] : $this->$property;
        }

        $this->id     = $id;
        $this->object = $this->get_object_id();
    }

    /**
     * Main callback for meta
     *
     * @since 0.2
     */
    public function output()
    {
        // Nonce field for validation
        wp_nonce_field('cuztom_meta', 'cuztom_nonce');

        Cuztom::view('meta/meta', array(
            'box' => $this
        ));
    }

    /**
     * Normal save method to save all the fields in a metabox
     *
     * @param int   $object Object id
     * @param array $values Array of values
     * @since 2.6
     */
    public function save($object, $values)
    {
        // Return when empty
        if (Cuztom::is_empty($values)) {
            return;
        }

        // Loop through each field
        foreach ($this->data as $id => $field) {
            $field->save($object, $values);
        }
    }

    /**
     * This method builds the complete array with the right key => value pairs
     *
     * @param  array $data
     * @return array
     * @since 1.1
     */
    public function build($data)
    {
        global $cuztom;

        $values = $this->get_meta_values();

        if (is_array($data) && !empty($data)) {
            foreach ($data as $type => $field) {
                // General stuff
                $field['meta_type'] = $this->meta_type;
                $field['object']    = $this->object;

                // Tabs / accordion
                if (is_string($type) && ($type == 'tabs' || $type == 'accordion')) {
                    $tabs = ($type == 'tabs' ? new Tabs($field) : new Accordion($field));

                    // Build and add
                    $tabs->build($field['panels'], $values);
                    $cuztom->data[$this->id][$tabs->id] = $tabs;
                }

                // Bundle
                elseif (is_string($type) && $type == 'bundle') {
                    $field['value'] = @$values[$field['id']][0];
                    $bundle         = new Bundle($field);

                    // Build and add
                    $bundle->build($field['fields'], $values);
                    $cuztom->data[$this->id][$bundle->id] = $bundle;
                }

                // Fields
                else {
                    $field['value'] = @$values[$field['id']][0];
                    $field = Field::create($field);

                    $cuztom->data[$this->id][$field->id] = $field;
                }
            }

            $this->fields = $cuztom->data[$this->id];
        }

        return $cuztom->data[$this->id];
    }

    /**
     * Check what kind of meta we're dealing with
     *
     * @param  string  $meta_type
     * @return boolean
     * @since  2.3
     */
    public function is_meta_type($meta_type)
    {
        return $this->meta_type == $meta_type;
    }

    /**
     * Adds multipart support to form
     *
     * @since 0.2
     */
    public static function edit_form_tag()
    {
        echo ' enctype="multipart/form-data"';
    }
}
