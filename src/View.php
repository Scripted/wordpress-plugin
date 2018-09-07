<?php

namespace Scripted;

use Twig_Environment;
use Twig_Error_Loader;
use Twig_Filter;
use Twig_Function;
use Twig_Loader_Filesystem;

class View
{
    /**
     * Twig template rendering instance.
     *
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * Creates new instance of view object.
     */
    private function __construct()
    {
        $loader = new Twig_Loader_Filesystem(Config::getTemplatesPath());
        $this->twig = new Twig_Environment($loader);
        $this->twig->addGlobal('logoUrl', Config::getLogoUrl());
        $this->twig->addGlobal('filter', WordPressApi::getInput('filter'));
        $this->twig->addGlobal('auth', WordPressApi::getInput('auth'));
        $this->twig->addFilter(
            new Twig_Filter('trim_quotes', [Tools\ContentFormatter::class, 'trimQuotes'])
        );
        $this->twig->addFunction(
            new Twig_Function('get_job_ajax_preview_url', [JobsPage::class, 'getJobAjaxPreviewUrl'])
        );
        $this->twig->addFunction(
            new Twig_Function('get_job_filters', [JobsPage::class, 'getJobFilters'])
        );
        $this->twig->addFunction(
            new Twig_Function('get_post_edit_url', [WordPressApi::class, 'getPostEditUrl'])
        );
        $this->twig->addFunction(
            new Twig_Function('get_post_id_for_job', [JobsPage::class, 'getPostIdForJob'])
        );
    }

    /**
     * Returns an existing, or creates a new, View object.
     *
     * @return View
     */
    public static function getInstance()
    {
        static $instance;

        if ($instance === null) {
            $instance = new View();
        }

        return $instance;
    }

    /**
     * Attempts to build an HTML view from the given parameters. Returns null
     * if something unexpected happens.
     *
     * @param  string $templatePath
     * @param  array  $parameters
     *
     * @return string|null
     */
    public static function render($templatePath, array $parameters = array())
    {
        try {
            $templatePath = preg_replace('/\./', '/', $templatePath);

            $templatePath .= '.html';

            $template = static::getInstance()->twig->load($templatePath);

            return $template->render($parameters);
        } catch (Twig_Error_Loader $e) {
            Config::log((string) $e);
        }

        return null;
    }
}
