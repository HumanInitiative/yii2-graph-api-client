<?php

namespace humaninitiative\graph\mailer\client;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

class ApiMailer extends Component
{
    public $apiUrl;
    public $viewPath = '@app/mail';
    public $htmlLayout = 'layouts/html';
    public $textLayout = 'layouts/text';

    /**
     * Initializes the component.
     * 
     * This method is called after the object is instantiated.
     * It is called once, immediately after the object is instantiated.
     * You should override this method to perform initialization of the component.
     * 
     * @throws InvalidConfigException if the required configuration is missing.
     */
    public function init()
    {
        parent::init();
        if (empty($this->apiUrl)) {
            throw new InvalidConfigException('Config ApiMailer (apiUrl) is required.');
        }
    }

    /**
     * Returns the API URL to send emails.
     * @return string the API URL.
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Compose a new message.
     *
     * This function will return a new message, and if a view is specified,
     * it will render the content of the view and set it to the message.
     *
     * @param string|null $view The name of the view to render and set to the message.
     * @param array $params The parameters to pass to the view.
     *
     * @return ApiMailerMessage The composed message.
     */
    public function compose($view = null, array $params = [])
    {
        $message = new ApiMailerMessage($this);

        if ($view !== null) {
            $this->renderContent($message, $view, $params);
        }

        Yii::configure($message, $params);
        return $message;
    }

    /**
     * Renders the content of a view and sets it to the message.
     *
     * This function will first render the HTML view of the specified view,
     * and then render the text view of the specified view. If the
     * htmlLayout or textLayout properties are set, it will
     * render the content of the view inside the layout and set
     * the rendered content to the message.
     *
     * @param ApiMailerMessage $message The message to render the content for.
     * @param string $view The name of the view to render.
     * @param array $params The parameters to pass to the view.
     *
     * @return void
     */
    protected function renderContent($message, $view, $params = [])
    {
        $params['message'] = $message;
        $viewComponent = Yii::$app->view;

        // Render HTML Body
        $htmlViewFile = $this->findViewFile($view, 'html');
        if ($htmlViewFile !== null) {
            $htmlContent = $viewComponent->renderFile($htmlViewFile, $params);
            if ($this->htmlLayout) {
                $layoutFile = Yii::getAlias($this->viewPath) . '/' . $this->htmlLayout . '.php';
                $htmlContent = $viewComponent->renderFile($layoutFile, ['content' => $htmlContent, 'message' => $message], $this);
            }
            $message->setHtmlBody($htmlContent);
        }

        // Render Text Body
        $textViewFile = $this->findViewFile($view, 'text');
        if ($textViewFile !== null) {
            $textContent = $viewComponent->renderFile($textViewFile, $params);
            if ($this->textLayout) {
                $layoutFile = Yii::getAlias($this->viewPath) . '/' . $this->textLayout . '.php';
                $textContent = $viewComponent->renderFile($layoutFile, ['content' => $textContent, 'message' => $message], $this);
            }
            $message->setTextBody($textContent);
        }
    }

    /**
     * Finds a view file in the specified view path.
     * The view file can be either a specific view file (e.g. 'view-html.php') or a generic view file (e.g. 'view.php').
     * If the view file is found, the path to the view file is returned, otherwise null is returned.
     *
     * @param string $view The name of the view to find.
     * @param string $type The type of the view to find (e.g. 'html' or 'text').
     *
     * @return string|null The path to the view file, or null if not found.
     */
    protected function findViewFile($view, $type)
    {
        $viewPath = Yii::getAlias($this->viewPath);
        
        // find specific view
        $specificViewFile = $viewPath . '/' . $view . '-' . $type . '.php';
        if (is_file($specificViewFile)) {
            return $specificViewFile;
        }

        // find generic view
        $genericViewFile = $viewPath . '/' . $view . '.php';
        if (is_file($genericViewFile)) {
            return $genericViewFile;
        }

        return null;
    }
}