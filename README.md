oxymel
======

Oxymel is a library for building XML with a sweet interface.

```php
$oxymel = new Oxymel;
echo $oxymel->xml->html->contains
  ->head->contains
    ->meta(array('charset' => 'utf-8'))
    ->title("How to seduce dragons")
    ->end
  ->body(array('class' => 'story'))->contains
    ->h1('How to seduce dragons')
    ->h2('The fire manual')
    ->p('Once upon a time in a distant land there was an dragon.')
    ->p('In another very distant land')->contains
    ->text(' there was a very ')->strong('strong')->text(' warrrior')->end
	->p->contains->cdata('<b>sadad</b>');

```
