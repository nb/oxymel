<?php
require_once __DIR__ . '/Oxymel.php';
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
</baba>', $this->x->baba->contains->dyado->end );
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
		$this->a("<a>baba<x/></a>", $this->x->a('baba')->contains->x->end );
	}

	function test_comment() {
		$this->a('<!--baba-->', $this->x->comment('baba'));
	}

	function test_two_invocations() {
		$this->x->baba();
		$this->x->dyado();
		$this->a( "<baba/>
<dyado/>", $this->x );
	}

	function test_double_end() {
		$this->a( "<out>
  <mid>
    <in/>
  </mid>
</out>
<nextout/>", $this->x->out->contains->mid->contains->in->end->end->nextout );
	}

	function test_end_without_contains() {
		$this->setExpectedException( 'OxymelException' );
		$this->x->end->baba;
	}

	function test_leading_contains() {
		$this->setExpectedException( 'OxymelException' );
		$this->a( '<baba/>', $this->x->contains->baba );
	}

	function test_consecutive_contains_should_error() {
		$this->setExpectedException( 'OxymelException' );
		$this->x->baba->contains->contains->dyado->end->wink;
	}

	function test_end_without_mathcing_contains_but_with_enough_parents() {
		$this->setExpectedException( 'OxymelException' );
		$this->x->contains->baba->end->end->baba;
	}

	function test_contains_after_newly_initialized_dom_should_error() {
		$this->setExpectedException( 'OxymelException' );
		$this->x->baba->open_rss->contains->baba->end;
	}

	private function a($value, $x) {
		$this->assertEquals( $value . "\n", $x->to_string());
	}
}
