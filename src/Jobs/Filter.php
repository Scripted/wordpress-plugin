<?php

namespace Scripted\Jobs;

class Filter
{
    /**
     * Filter name
     *
     * @var string
     */
    public $name;

    /**
     * Filter slug
     *
     * @var string
     */
    public $slug;

    /**
     * Is filter "selected"
     *
     * @var bool
     */
    public $selected;

    /**
     * Creates new filter instance.
     *
     * @param string  $name
     * @param string  $slug
     * @param boolean $selected
     */
    public function __construct($name, $slug, $selected = false)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->selected = (bool) $selected;
    }
}
