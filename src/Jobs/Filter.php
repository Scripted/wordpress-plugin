<?php

namespace Scripted\Jobs;

class Filter
{
    public $name;

    public $slug;

    public $selected;

    public function __construct($name, $slug, $selected = false)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->selected = (bool) $selected;
    }
}
