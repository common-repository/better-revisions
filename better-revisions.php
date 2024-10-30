<?php
/*
   Plugin Name: Better Revisions
   Plugin URI: https://www.silvius.at/
   Version: 0.5
   Author: Silvius Lehner
   Description: Adds the "Post Author", "Post Date", "Post Status", "Comment Status", "Ping Status", "Post Password", "Permalink", "Post Parent" and "Menu Order" to the revision system!
   Text Domain: better-revisions
   Domain Path: /languages/
   License: GPLv3
*/

/**
 * don't call this file directly
 */
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class WP_Better_Revisions {

	/**
	 * declare a metakey prefix for prevent collision with other metas
	 * the underscore at the beginning prevents from showing key inside wordpress standard post metafields
	 *
	 * @var string
	 */
	private $metaPrefix = '_sl23_';

	/**
	 * @var string
	 */
	private $ajaxCall = 'sl_set_revision_meta';

	/**
	 * @var string
	 */
	private $nonceString = 'slRv0nah23ggGfq1H';

	/**
	 * https://github.com/WordPress/gutenberg/issues/10711
	 * https://core.trac.wordpress.org/ticket/45114
	 * https://github.com/WordPress/gutenberg/issues/12903
	 *
	 * TODO: if somneone uses Gutenberg and the old Form for Customfields together - than the post_updated (btw. save_post) hook is called twice - we need a workaround for none dpublicate revisions in this case
	 *
	 * @var bool
	 */
	private $firstTrigger = true;

	/**
	 * We check if Gutenberg is used
	 *
	 * @var bool
	 */
	private $gutenbergIsUsed = false;

	/**
	 * declare array with revision-meta-names as keys and
	 * the field description for the revision screen as translateable values
	 *
	 * @return array
	 */
	private function revisionMetaKeys() {
		$translateable_revisionMetaKeys = array(
			'post_author'       => __( 'Author', 'better-revisions' ),
			'post_date'         => __( 'Published on', 'better-revisions' ),
			'post_date_gmt'     => __( 'Published on GMT', 'better-revisions' ),
			'post_status'       => __( 'Status', 'better-revisions' ),
			'comment_status'    => __( 'Allow comments', 'better-revisions' ),
			'ping_status'       => __( 'Allow  trackbacks and pingbacks', 'better-revisions' ),
			'post_password'     => __( 'Post Password', 'better-revisions' ),
			'post_name'         => __( 'Permalink', 'better-revisions' ),
			'post_modified'     => __( 'Post Modified Date', 'better-revisions' ),
			'post_modified_gmt' => __( 'Post Modified Date GMT', 'better-revisions' ),
			'post_parent'       => __( 'Parent', 'better-revisions' ),
			'menu_order'        => __( 'Order', 'better-revisions' ),
		);

		return $translateable_revisionMetaKeys;
	}

	/**
	 * constructor function
	 */
	public function __construct() {
		/**
		 * fire wordpress actions and filters on 'init'
		 */
		add_action( 'init', array( &$this, 'sl_wp_actions_and_filters' ) );
		// load textdomain
		add_action( 'init', array( $this, 'sl_load_textdomain' ) );
	}

	/**
	 * loading textdomain
	 */
	public function sl_load_textdomain() {
		load_plugin_textdomain( 'better-revisions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * contains all necessary wordpress hooks
	 */
	public function sl_wp_actions_and_filters() {
		/**
		 * Actions
		 */
		// set the needed plugin variables
		add_action( 'wp_loaded', array( $this, 'setup_variables' ) );
		// save post data to revision meta
		add_action( 'post_updated', array( $this, 'sl_save_postdata_to_revision_meta' ), 10, 3 );
		// restore revision meta to original post data
		add_action( 'wp_restore_post_revision', array( $this, 'sl_restore_revision' ), 10, 2 );
		// get ajax data (custom autosave) and save them to revision meta
		add_action( 'wp_ajax_' . $this->ajaxCall, array( $this, 'sl_ajax_save_postdata_to_revision_meta' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_revisions_script' ) );
		/**
		 * Filters
		 */
		// add additional fields to the revision screen
		add_filter( '_wp_post_revision_fields', array( $this, 'sl_all_revision_fields' ), 10, 2 );
		// filter specific revision fields output
		add_filter( '_wp_post_revision_field_' . $this->metaPrefix . 'post_author',
			array( $this, 'sl_filter_revision_field' ),
			10,
			3 );
		add_filter( '_wp_post_revision_field_' . $this->metaPrefix . 'post_parent',
			array( $this, 'sl_filter_revision_field' ),
			10,
			3 );
		add_filter( '_wp_post_revision_field_' . $this->metaPrefix . 'comment_status',
			array( $this, 'sl_filter_revision_field' ),
			10,
			3 );
		add_filter( '_wp_post_revision_field_' . $this->metaPrefix . 'ping_status',
			array( $this, 'sl_filter_revision_field' ),
			10,
			3 );
		add_filter( '_wp_post_revision_field_' . $this->metaPrefix . 'post_modified',
			array( $this, 'sl_filter_revision_field' ),
			10,
			3 );
		add_filter( '_wp_post_revision_field_' . $this->metaPrefix . 'post_modified_gmt',
			array( $this, 'sl_filter_revision_field' ),
			10,
			3 );
	}

	public function add_revisions_script() {

		global $pagenow;
		if (
			!in_array($pagenow,['post-new.php','post.php'])
		) {
			return;
		}

		global $post;
		$postID = $post->ID;
		$userID = get_current_user_id();

		// loading js
		wp_register_script( 'revisions-js', plugins_url('js/revisions.js',__FILE__ ), array('jquery-core'), false, false );
		wp_enqueue_script( 'revisions-js' );
		wp_localize_script( 'revisions-js', 'revisions_js_object',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'ajaxCall' => $this->ajaxCall,
				'nonce' => wp_create_nonce($this->nonceString),
				'postID' => $postID,
				'userID' => $userID,
				'gutenbergIsUsed' => $this->gutenbergIsUsed
			)
		);
	}

	/**
	 * function to set the needed variables for this plugin
	 */
	public function setup_variables() {
		$this->gutenbergIsUsed = $this->check_if_gutenberg_is_active();
	}

	/**
	 * check if Gutenberg is active
	 *
	 * @return bool
	 */
	private function check_if_gutenberg_is_active() {
		$gutenberg   = false;
		$blockEditor = false;

		if ( has_filter( 'replace_editor', 'gutenberg_init' ) ) {
			// Gutenberg is active
			$gutenberg = true;
		}

		if ( version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' ) ) {
			// Block editor
			$blockEditor = true;
		}

		if ( ! $gutenberg && ! $blockEditor ) {
			return false;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
			return true;
		}

		$useBlockEditor = ( get_option( 'classic-editor-replace' ) === 'no-replace' );

		return $useBlockEditor;
	}

	/**
	 * saves the actual post data to the cloned revision meta
	 * and alo saves the old post data to the latest revision (not cloned revision!) for backwards compatibility
	 *
	 * @param $post_ID
	 * @param $post_after
	 * @param $post_before
	 */
	public function sl_save_postdata_to_revision_meta( $post_ID, $post_after, $post_before ) {
		$metaPrefix       = $this->metaPrefix;
		$revisionMetaKeys = $this->revisionMetaKeys();
		$revisions        = $this->sl_get_revisions_ids( $post_ID );
		if ( $revisions === false ) {
			return;
		}
		$revID   = $revisions['revisionID'];
		$cloneID = $revisions['cloneID'];

		if ( $cloneID === false ) {
			return;
		}
		$cloneRev = get_post( $cloneID );
		if ( $post_after->post_modified_gmt === $cloneRev->post_modified_gmt ) {
			foreach ( $revisionMetaKeys as $key => $description ) {
				add_metadata( 'post', $cloneID, $metaPrefix . $key, $post_after->$key, true );
				if ( $revID !== false ) {
					add_metadata( 'post', $revID, $metaPrefix . $key, $post_before->$key, true );
				}
			}
		} else {
			error_log( 'no actual revision was made!' );
		}
	}

	/**
	 * adds all necessary revision meta fields to the revision screen
	 *
	 * @param $fields
	 * @param $post
	 *
	 * @return mixed
	 */
	public function sl_all_revision_fields( $fields, $post ) {
		$metaPrefix       = $this->metaPrefix;
		$revisionMetaKeys = $this->revisionMetaKeys();
		foreach ( $revisionMetaKeys as $key => $description ) {
			$fields[ $metaPrefix . $key ] = $description;
		}

		return $fields;
	}

	/**
	 * filters some revision meta fields for showing more usefull values
	 *
	 * @param $value
	 * @param $field
	 * @param $post
	 *
	 * @return string
	 */
	public function sl_filter_revision_field( $value, $field, $post ) {
		$metaPrefix = $this->metaPrefix;
		$revision   = $post;
		if ( ! empty( $revision ) ) {
			if ( $field == $metaPrefix . 'post_author' ) {
				$authorID = get_metadata( 'post', $revision->ID, $field, true );

				if ( ! empty( $authorID ) ) {
					$authorData = get_userdata( $authorID );

					return $authorData->data->display_name . ' (' . $authorData->data->user_nicename . ')';
				} else {
					return '';
				}
			} elseif ( $field == $metaPrefix . 'post_parent' ) {
				$postParent = get_metadata( 'post', $revision->ID, $field, true );
				if ( $postParent != 0 ) {
					return get_the_title( $postParent );
				} else {
					return __( '(no parent)', 'better-revisions' );
				}
			} elseif ( $field == $metaPrefix . 'comment_status' || $field == $metaPrefix . 'ping_status' ) {
				$allowCommTrack = get_metadata( 'post', $revision->ID, $field, true );
				if ( $allowCommTrack == 'open' ) {
					return __( 'yes', 'better-revisions' );
				} else {
					return __( 'no', 'better-revisions' );
				}
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	/**
	 * after a revision was restored we restore also the data from the revision meta
	 *
	 * @param $post_id
	 * @param $revision_id
	 */
	public function sl_restore_revision( $post_id, $revision_id ) {
		global $wpdb;
		$metaPrefix       = $this->metaPrefix;
		$revisionMetaKeys = $this->revisionMetaKeys();
		$revision         = get_post( $revision_id );
		$revisionData     = array();
		foreach ( $revisionMetaKeys as $key => $description ) {
			$metaData = get_metadata( 'post', $revision->ID, $metaPrefix . $key, true );
			if ( $metaData !== false ) {
				$revisionData[ $key ] = $metaData;
			}
		}
		$ok        = $wpdb->update( $wpdb->prefix . 'posts',
			$revisionData,
			array(
				'ID' => $post_id,
			) );
		$revisions = wp_get_post_revisions( $post_id );
		$cloneRev  = current( $revisions );
		$cloneID   = $cloneRev->ID;
		foreach ( $revisionData as $key => $value ) {
			update_metadata( 'post', $cloneID, $metaPrefix . $key, $value );
		}

		return;
	}

	/**
	 * we catch the post data from the ajax call and save them to the autosave-revision-meta
	 */
	public function sl_ajax_save_postdata_to_revision_meta() {
		// check nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], $this->nonceString) ) {
			die ( 'no');
		}

		$metaPrefix  = $this->metaPrefix;
		$postID      = intval( $_POST['postID'] );
		$userID      = intval( $_POST['userID'] );
		$postData    = $_POST['meta'];
		$revsisonIDs = $this->sl_get_revisions_ids( $postID, $userID );
		if (
			isset( $revsisonIDs['autosaveID'] )
			&& is_array( $postData )
		) {
			$revisionData = $this->sl_ajax_prepare_postdata_for_saving( $postData );
			$autosaveID   = intval( $revsisonIDs['autosaveID'] );
			foreach ( $revisionData as $key => $value ) {
				add_metadata( 'post', $autosaveID, $metaPrefix . $key, $value, true );
				update_metadata( 'post', $autosaveID, $metaPrefix . $key, $value );
			}
			echo 'success';
			wp_die();
		}
		echo 'error';
		wp_die();
	}

	/**
	 * before we can save the revision meta to the autosave we must filter the ajax post data
	 *
	 * @param $postData
	 *
	 * @return array
	 */
	private function sl_ajax_prepare_postdata_for_saving( $postData ) {
		$revisionMetaKeys  = $this->revisionMetaKeys();
		$revisionData      = array();
		$postData_date     = $postData['post_date'];
		$post_date         = $postData_date['year'] . "-" . $postData_date['month'] . "-" . $postData_date['day'] . " " . $postData_date['hour'] . ":" . $postData_date['minute'] . ":" . $postData_date['second'];
		$post_date_gmt     = get_gmt_from_date( $post_date );
		$post_modified     = current_time( 'mysql', 0 );
		$post_modified_gmt = current_time( 'mysql', 1 );
		if ( $postData['visibility'] === 'private' ) {
			$post_status = 'private';
		} elseif ( $postData['post_status'] === 'pending' ) {
			$post_status = 'pending';
		} elseif ( $postData['post_status'] === 'draft' ) {
			$post_status = 'draft';
		} elseif ( ! empty( $postData['post_password'] ) ) {
			$post_status = 'publish';
		} elseif ( strtotime( $post_date ) > strtotime( $post_modified ) ) {
			$post_status = 'future';
		} else {
			$post_status = 'publish';
		}
		// we go through the revisionMetaKeys
		foreach ( $revisionMetaKeys as $key => $description ) {
			if ( $key === 'post_date' ) {
				$revisionData[ $key ] = $post_date;
			} elseif ( $key === 'post_date_gmt' ) {
				$revisionData[ $key ] = $post_date_gmt;
			} elseif ( $key === 'post_modified' ) {
				$revisionData[ $key ] = $post_modified;
			} elseif ( $key === 'post_modified_gmt' ) {
				$revisionData[ $key ] = $post_modified_gmt;
			} elseif ( $key === 'comment_status' ) {
				if ( isset( $postData['comment_status'] ) ) {
					$revisionData[ $key ] = $postData['comment_status'];
				} else {
					$revisionData[ $key ] = 'closed';
				}
			} elseif ( $key === 'ping_status' ) {
				if ( isset( $postData['ping_status'] ) ) {
					$revisionData[ $key ] = $postData['ping_status'];
				} else {
					$revisionData[ $key ] = 'closed';
				}
			} elseif ( $key === 'post_parent' ) {
				if ( isset( $postData['post_parent'] ) ) {
					$revisionData[ $key ] = intval( $postData['post_parent'] );
				} else {
					$revisionData[ $key ] = 0;
				}
			} elseif ( $key === 'post_status' ) {
				$revisionData[ $key ] = $post_status;
			} elseif ( $key === 'post_password' ) {
				$revisionData[ $key ] = $postData['post_password'];
			} else {
				if ( isset( $postData[ $key ] ) ) {
					if ( is_numeric( $postData[ $key ] ) ) {
						$revisionData[ $key ] = intval( $postData[ $key ] );
					} else {
						$revisionData[ $key ] = $postData[ $key ];
					}
				}
			}
		}

		return $revisionData;
	}

	/**
	 * for a given post id, we get the ids of the cloned revision and of the latest real revision
	 * and optional of the autosave revision of the current user
	 *
	 * @param int $postID
	 * @param bool $userID
	 *
	 * @return array|bool
	 */
	private function sl_get_revisions_ids( $postID, $userID = false ) {
		$return    = array(
			'autosaveID' => false,
			'cloneID'    => false,
			'revisionID' => false,
		);
		$revisions = wp_get_post_revisions( $postID );
		if ( empty( $revisions ) ) {
			return false;
		}
		foreach ( $revisions as $key => $revOject ) {
			if ( strpos( $revOject->post_name, 'autosave' ) !== false ) {
				if ( $userID !== false ) {
					if ( $revOject->post_author == $userID ) {
						$return['autosaveID'] = (int) $revOject->ID;
					}
				}
				unset( $revisions[ $key ] );
			}
		}
		$ordered = array();
		foreach ( $revisions as $key => $revOject ) {
			$ordered[ $key ]   = strtotime( $revOject->post_modified_gmt );
			$revisions[ $key ] = (array) $revOject;
		}
		array_multisort( $ordered, SORT_DESC, $revisions );
		if ( isset( $revisions[0] ) ) {
			$return['cloneID'] = (int) $revisions[0]['ID'];
		}
		if ( isset( $revisions[1] ) ) {
			$return['revisionID'] = (int) $revisions[1]['ID'];
		}

		return $return;
	}
}

$sl_WP_Better_Revisions = new WP_Better_Revisions;
