<?php
error_reporting(E_ALL);

class Oxymel {
	private $go_down_next_call = false;
	private $go_up_next_call = 0;
	private $xml;
	private $dom;
	private $current_element;

	function __construct() {
		$this->indentation_level = 0;
		$this->xml = '';
		$this->init_new_dom();
	}

	private function init_new_dom() {
		unset( $this->dom, $this->current_element );
		$this->dom = new DOMDocument();
		$this->dom->formatOutput = true;
		$this->current_element = $this->dom;
	}

	private function xml_from_dom() {
		// TODO: indent every line
		$xml = '';
		foreach( $this->dom->childNodes as $child ) {
			$xml .= $this->dom->saveXML( $child ) . "\n";
		}
		return $xml;
	}

	function __call( $name, $args ) {
		array_unshift( $args, $name );
		return call_user_func_array( array( $this, 'tag' ), $args );
	}

	function __get( $name ) {
		return $this->$name();
	}

	function contains() {
		$this->go_down_next_call = true;
		return $this;
	}

	function end() {
		$this->go_up_next_call++;
		return $this;
	}

	function tag( $name, $content_or_attributes = null, $attributes = array() ) {
		$content = null;
		if ( !$attributes ) {
			if ( is_array( $content_or_attributes ) )
				$attributes = $content_or_attributes;
			else
				$content = $content_or_attributes;
		} else {
			$content = $content_or_attributes;
		}
		$is_open =  0 === strpos( $name, 'open_' );
		$is_close =  0 === strpos( $name, 'close_' );
		$name = preg_replace("/^(open|close)_/", '', $name );

		if ( $content )
			$element = $this->dom->createElement( $name, $content );
		else
			$element = $this->dom->createElement( $name );
		foreach( $attributes as $attribute_name => $attribute_value ) {
			$element->setAttribute( $attribute_name, $attribute_value );
		}
		if ( $is_open ) {
			$this->xml .= $this->xml_from_dom();
			$tag = $this->dom->saveXML($element);
			$this->xml .= str_replace( '/>', '>', $tag ) . "\n";
			$this->indentation_level++;
			$this->init_new_dom();
		} elseif ( $is_close ) {
			$this->xml .= $this->xml_from_dom();
			$this->xml .= "</$name>\n";
			$this->indentation_level++;
			$this->init_new_dom();
		} else {
			$this->append( $element );
		}
		return $this;
	}

	function cdata( $text ) {
		$this->append( $this->dom->createCDATASection( $text ) );
		return $this;
	}

	function text( $text ) {
		$this->append( $this->dom->createTextNode( $text ) );
		return $this;
	}

	function xml() {
		$this->append( $this->dom->createProcessingInstruction( 'xml', 'version="1.0" encoding="UTF-8"' ) );
		return $this;
	}

	function raw(  $raw_xml ) {
		$fragment = $this->dom->createDocumentFragment();
		$fragment->appendXML($raw_xml);
		$this->append( $fragment );
		return $this;
	}

	private function append( $element ) {
		if ( $this->go_down_next_call ) {
			$this->current_element = $this->latest_inserted;
			$this->go_down_next_call = false;
		}
		if ( $this->go_up_next_call ) {
			//TODO: check if there is a parentNode
			while ( $this->go_up_next_call ) {
				$this->current_element = $this->current_element->parentNode;
				$this->go_up_next_call--;
			}
			$this->go_up_next_call = 0;
		}
		$this->latest_inserted = $this->current_element->appendChild($element);
	}

	function __toString() {
		return $this->xml .= $this->xml_from_dom();
	}
}

class TestX extends PHPUnit_Framework_TestCase {
	function __construct() {
		$this->x = new Oxymel;
	}
	function test_self_closing() {
		$this->a('<baba/>', $this->x->baba);
	}
	function test_self_closing_method() {
		$this->a('<baba/>', $this->x->baba());
	}
	function test_attribute_no_content() {
		$this->a('<baba a="b" c="d"/>', $this->x->baba( array( 'a' => 'b', 'c' => 'd' ) ) );
	}

	function test_content_no_attribute() {
		$this->a('<baba>content</baba>', $this->x->baba( 'content' ) );
	}

	function test_escapiing_of_content() {
		$this->a('<baba>&lt;</baba>', $this->x->baba( '<' ) );
	}

	function test_escaping_of_attributes() {
		$this->a('<baba a="&lt;"/>', $this->x->baba( array( 'a' => '<' ) ) );
	}

	function test_content_and_attributes() {
		$this->a('<baba a="b" c="d">content</baba>', $this->x->baba( 'content', array( 'a' => 'b', 'c' => 'd' ) ) );
	}

	function x_test_content_and_attributes_error() {
		$this->a('<baba a="b" c="d">content</baba>', $this->x->baba( array( 'a' => 'b', 'c' => 'd' ), 'content' ) );
	}

	function test_go_down() {
		$this->a('<baba>
  <dyado/>
</baba>', $this->x->baba->contains->dyado );
	}

	function test_go_down_and_up() {
		$this->a("<level0>
  <level1/>
</level0>
<level0/>", $this->x->level0->contains->level1->end->level0 );
	}

	function test_cdata() {
		$this->a('<baba><![CDATA[content]]></baba>', $this->x->baba->contains->cdata('content')->end);
	}

	function test_raw() {
		$this->a('<baba>
  <dyado/>
</baba>', $this->x->baba->contains->raw('<dyado></dyado>')->end);
	}

	function x_test_only_up_error() {
		$this->a('', $this->x->end );
	}

	function test_open_in_the_end() {
		$this->a("<baba/>
<newtag>", $this->x->baba->open_newtag );
	}

	function test_open_with_attributes() {
		$this->a("<baba/>
<newtag a=\"b\">", $this->x->baba->open_newtag( array( 'a' => 'b' ) ) );
	}

	function test_dom_open_dom() {
		$this->a("<baba/>
<newtag>
<baba/>", $this->x->baba->open_newtag->baba );
	}

	function test_close_in_the_beginning() {
		$this->a("</oldtag>
<baba/>", $this->x->close_oldtag->baba );
	}

	function test_dom_close_dom() {
		$this->a("<baba/>
</oldtag>
<baba/>", $this->x->baba->close_oldtag->baba );
	}

	function test_baba() {
		$this->a("<a>baba<x/></a>", $this->x->a('baba')->contains->x );
	}

	function test_long() {
		echo $this->x->xml->html->contains
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
	}

	private function a($value, $x) {
		$this->assertEquals( $value . "\n", (string)$x);
	}
}

