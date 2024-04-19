<?php

require_once __DIR__ . '/../vendor/autoload.php';

use slow\util\template\tiny;
use slow\util\html\soup;
use slow\util\html\tag;
use slow\util\html\node;

class errors {
    public array $err = [];
    function string_on($name) {
        return 'please enter email address';
    }
}

class user {

    public $errors;
    public string $email;

    function __construct() {
        $this->errors = new errors;
    }
}

print "memory: " . hum_size(memory_get_usage()) . "\n";
print "\n--\n";
print "use tiny\n";
$t1 = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $tpl = __DIR__ . '/test.tpl.html';
    tiny::add_snippets(file_get_contents($tpl));
    $user = new user;
    $user->email = '';

    for ($k = 0; $k < 20; $k++) {
        $data = [
            'class' => 'form-control', 'id' => 'email', 'name' => 'email',
            'placeholder' => 'm.mustermann@example.com', 'label' => 'email', 'error' => $user->errors->string_on('email')
        ];
        $form = tag::tag('input', $data);
        $data['form'] = $form;
        tiny::render_snippet('general', $data);
    }
}
print_r(benchmark_time($t1));
print "memory: " . hum_size(memory_get_usage()) . "\n";
# - #

print "\n--\n";
print "use tag/soup\n";
$t1 = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $user = new user;
    $user->email = '';
    $html = new soup($user, 'user[%s]');

    for ($k = 0; $k < 20; $k++) {
        $els = $html->input('email', 'E-Mail', value: '');
        $els->wrap('.frow');
        $out = (string) $els;
    }
}
print_r(benchmark_time($t1));
print "memory: " . hum_size(memory_get_usage()) . "\n";
# - #

print "\n--\n";
print "use node/abbr\n";
$t1 = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $user = new user;
    $user->email = '';

    for ($k = 0; $k < 20; $k++) {
        $els = node::new('.frow>label`label+input.form-control[type=text]')
            ->attr('value', '')->insert_after(new node(class: 'invalid-feedback', children: 'please input your email'))
            ->get('label')->attr("for", 'email')->content('E-Mail');
        $out = $els->render_doc();
    }
}
print_r(benchmark_time($t1));
print "memory: " . hum_size(memory_get_usage()) . "\n";

# - #

print "\n--\n";
print "use node/abbr + tmpl\n";
$t1 = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $user = new user;
    $user->email = '';

    $tpl = node::new('.frow>label[:for=id]{{label}}+input.form-control[:name=name :value=val type=text]+div')->template();
    $tpl(['name' => 'email', 'id' => 'email', 'val' => '']);

    for ($k = 0; $k < 20; $k++) {
        $out = $tpl(['name' => 'email', 'id' => 'email', 'val' => '']);
    }
}
print_r(benchmark_time($t1));
print "memory: " . hum_size(memory_get_usage()) . "\n";
