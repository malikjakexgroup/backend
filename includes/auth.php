<?php

if (!defined('ABSPATH')) {
    exit;
}

// Simple token auth, no dependency. A random token is issued on register/login and its
// SHA-256 hash is stored in user meta; the raw token proves identity on later requests.
// ponytail: one token per user (a new login replaces the old). Add refresh/expiry later.

function bb_issue_token(int $user_id): string
{
    $token = bin2hex(random_bytes(32));
    update_user_meta($user_id, 'bb_token', hash('sha256', $token));
    return $token;
}

function bb_user_by_token(string $token): ?WP_User
{
    if ($token === '') {
        return null;
    }
    $users = get_users(array(
        'meta_key'   => 'bb_token',
        'meta_value' => hash('sha256', $token),
        'number'     => 1,
    ));
    return $users ? $users[0] : null;
}

function bb_bearer_token(WP_REST_Request $req): string
{
    $header = (string) $req->get_header('authorization');
    if (stripos($header, 'bearer ') === 0) {
        return trim(substr($header, 7));
    }
    return '';
}

function bb_user_public(WP_User $user): array
{
    return array(
        'id'    => $user->ID,
        'name'  => $user->display_name,
        'email' => $user->user_email,
    );
}

function bb_register_auth_routes(): void
{
    register_rest_route('books/v1', '/register', array(
        'methods'             => 'POST',
        'callback'            => 'bb_rest_register',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('books/v1', '/login', array(
        'methods'             => 'POST',
        'callback'            => 'bb_rest_login',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('books/v1', '/me', array(
        'methods'             => 'GET',
        'callback'            => 'bb_rest_me',
        'permission_callback' => '__return_true',
    ));
}

function bb_rest_register(WP_REST_Request $req)
{
    $name     = trim((string) $req->get_param('name'));
    $email    = sanitize_email((string) $req->get_param('email'));
    $password = (string) $req->get_param('password');

    if (!is_email($email) || strlen($password) < 6) {
        return new WP_Error('bad_request', 'A valid email and a password of at least 6 characters are required', array('status' => 400));
    }
    if (email_exists($email)) {
        return new WP_Error('exists', 'An account with this email already exists', array('status' => 409));
    }

    $user_id = wp_create_user($email, $password, $email); // username = email
    if (is_wp_error($user_id)) {
        return new WP_Error('create_failed', $user_id->get_error_message(), array('status' => 400));
    }
    if ($name !== '') {
        wp_update_user(array('ID' => $user_id, 'display_name' => $name));
    }

    $token = bb_issue_token($user_id);
    return rest_ensure_response(array('token' => $token, 'user' => bb_user_public(get_user_by('id', $user_id))));
}

function bb_rest_login(WP_REST_Request $req)
{
    $email    = sanitize_email((string) $req->get_param('email'));
    $password = (string) $req->get_param('password');

    $user = wp_authenticate($email, $password);
    if (is_wp_error($user)) {
        return new WP_Error('invalid', 'Wrong email or password', array('status' => 401));
    }

    $token = bb_issue_token($user->ID);
    return rest_ensure_response(array('token' => $token, 'user' => bb_user_public($user)));
}

function bb_rest_me(WP_REST_Request $req)
{
    $user = bb_user_by_token(bb_bearer_token($req));
    if (!$user) {
        return new WP_Error('unauthorized', 'Not logged in', array('status' => 401));
    }
    return rest_ensure_response(bb_user_public($user));
}
