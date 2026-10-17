<?php
/**
 * Shared request form. Pass context: dat-ban, lien-he, or tiec.
 */
if (!defined('ABSPATH')) exit;
$gns_context = 'lien-he';
if (isset($args['context'])) $gns_context = sanitize_key((string) $args['context']);
echo gns_request_form_html($gns_context);
