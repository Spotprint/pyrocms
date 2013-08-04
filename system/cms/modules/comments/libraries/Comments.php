<?php

use Pyro\Module\Comments\Model\Comment;

/**
 * Comments library
 *
 * @author		Phil Sturgeon
 * @author		PyroCMS Dev Team
 * @package		PyroCMS\Core\Modules\Comments\Libraries
 */
class Comments
{
	/**
	 * The name of the module in use
	 *
	 * @var	string
	 */
	protected $module;

	/**
	 * Singular language key
	 *
	 * @var	string
	 */
	protected $singular;

	/**
	 * Plural language key
	 *
	 * @var	string
	 */
	protected $plural;

	/**
	 * Entry for this, be it an auto increment id or string
	 *
	 * @var	string|int
	 */
	protected $entry_id;

	/**
	 * Title of the entry
	 *
	 * @var	string
	 */
	protected $entry_title;

	/**
	 * What is the URL of this entry?
	 *
	 * @var	string
	 */
	protected $entry_uri;

	/**
	 * Encrypted hash containing title, singular and plural keys
	 *
	 * @var	bool
	 */
	protected $entry_hash;

	/**
	 * Comment Count
	 *
	 * Setting to 0 by default.
	 *
	 * @var 	int
	 */
	protected $count = 0;

	/**
	 * Function to display a comment
	 *
	 * Reference is a actually an object reference, a.k.a. categorization of the comments table rows.
	 * The reference id is a further categorization on this. (For example, for example for
	 *
	 * @param	string	$module		The name of the module in use
	 * @param	string	$singular	Singular language key
	 * @param	string	$plural		Plural language key
	 * @param	string|int	$entry_id	Entry for this, be it an auto increment id or string, or null
	 */
	public function __construct($params)
	{
		ci()->lang->load('comments/comments');

		// This shouldnt be required if static loading was possible, but its not in CI
		if (is_array($params)) {
			// Required
			$this->module = $params['module'];
			$this->singular = $params['singular'];
			$this->plural = $params['plural'];

			// Overridable
			$this->entry_uri = isset($params['uri']) ? $params['uri'] : uri_string();

			// Optional
			isset($params['entry_id']) and $this->entry_id = $params['entry_id'];
			isset($params['entry_title']) and $this->entry_title = $params['entry_title'];
		}

		// TODO: Put this on an IoC when we have one
		$this->moduleManager = ci()->moduleManager;
	}

	/**
	 * Display comments
	 *
	 * @return	string	Returns the HTML for any existing comments
	 */
	public function display()
	{
		// Fetch comments, then process them
		$comments = $this->process(Comment::findByEntry($this->module, $this->singular, $this->entry_id));

		// Return the awesome comments view
		return $this->load_view('display', compact(array('comments')));
	}

	/**
	 * Display form
	 *
	 * @return	string	Returns the HTML for the comment submission form
	 */
	public function form()
	{
		// Return the awesome comments view
		return $this->load_view('form', array(
			'module'		=>	$this->module,
			'entry_hash'	=>	$this->encode_entry(),
			'comment'		=>  ci()->session->flashdata('comment'),
		));
	}

	/**
	 * Count comments
	 *
	 * @return	int	Return the number of comments for this entry item
	 */
	public function count()
	{
		return Comment::findByEntry($this->module, $this->singular, $this->entry_id, true)->count();
	}

	/**
	 * Count comments as string
	 *
	 * @return	string 	Language string with the total in it
	 */
	public function count_string($comment_count = null)
	{
		$total = ($comment_count) ? $comment_count : $this->count;

		return sprintf(lang("comments:counter_{$line}_label"), $total);
	}

	/**
	 * Function to process the items in an X amount of comments
	 *
	 * @param array $comments The comments to process
	 * @return array
	 */
	public function process($comments)
	{
		// Remember which modules have been loaded
		static $modules = array();

		foreach ($comments as &$comment) {
			// Override specified website if they are a user
			if ($comment->user_id and Settings::get('enable_profiles')) {
				$comment->website = site_url('user/'.$comment->user->username);
			}

			// We only want to load a lang file once
			if ( ! isset($modules[$comment->module])) {
				if ($this->moduleManager->moduleExists($comment->module)) {
					ci()->lang->load("{$comment->module}/{$comment->module}");

					$modules[$comment->module] = true;
				}
				// If module doesn't exist (for whatever reason) then sssh!
				else {
					$modules[$comment->module] = false;
				}
			}

			$comment->singular = lang($comment->entry_key) ?: humanize($comment->entry_key);
			$comment->plural = lang($comment->entry_plural) ?: humanize($comment->entry_plural);

			// Security: Escape any Lex tags
			foreach ($comment as $field => $value) {
				$comment->{$field} = escape_tags($value);
			}
		}

		return $comments;
	}

	/**
	 * Load View
	 *
	 * @return	string	HTML of the comments and form
	 */
	protected function load_view($view, $data)
	{
		$ext = pathinfo($view, PATHINFO_EXTENSION) ? '' : '.php';

		if (file_exists(ci()->template->get_views_path().'modules/comments/'.$view.$ext)) {
			// look in the theme for overloaded views
			$path = ci()->template->get_views_path().'modules/comments/';
		} else {
			// or look in the module
			list($path, $view) = Modules::find($view, 'comments', 'views/');
		}

		// add this view location to the array
		ci()->load->set_view_path($path);
		ci()->load->vars($data);

		return ci()->load->_ci_load(array('_ci_view' => $view, '_ci_return' => true));
	}

	/**
	 * Encode Entry
	 *
	 * @return	string	Return a hash of entry details, so we can send it via a form safely.
	 */
	protected function encode_entry()
	{
		return ci()->encrypt->encode(serialize(array(
			'id'			=>	$this->entry_id,
			'title'			=> 	$this->entry_title,
			'uri'			=>	$this->entry_uri,
			'singular'		=>	$this->singular,
			'plural'		=>	$this->plural,
		)));
	}

}
