<?php
error_reporting(E_ALL);

class Oxymel {
	private $go_down_next_call = false;
	private $go_up_next_call = 0;
	private $xml;
	private $dom;
	private $current_element;

	public function __construct() {
		$this->indentation_level = 0;
		$this->xml = '';
		$this->init_new_dom();
	}

	public function __toString() {
		return $this->xml .= $this->xml_from_dom();
	}

	public function __call( $name, $args ) {
		array_unshift( $args, $name );
		return call_user_func_array( array( $this, 'tag' ), $args );
	}

	public function __get( $name ) {
		return $this->$name();
	}

	public function contains() {
		$this->go_down_next_call = true;
		return $this;
	}

	public function end() {
		$this->go_up_next_call++;
		return $this;
	}

	public function tag( $name, $content_or_attributes = null, $attributes = array() ) {
		list( $content, $attributes ) = $this->get_content_and_attributes_from_tag_args( $content_or_attributes, $attributes );
		$is_open =  0 === strpos( $name, 'open_' );
		$is_close =  0 === strpos( $name, 'close_' );
		$name = preg_replace("/^(open|close)_/", '', $name );

		if ( !is_null( $content ) )
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

	public function cdata( $text ) {
		$this->append( $this->dom->createCDATASection( $text ) );
		return $this;
	}

	public function text( $text ) {
		$this->append( $this->dom->createTextNode( $text ) );
		return $this;
	}

	public function comment( $text ) {
		$this->append( $this->dom->createComment( $text ) );
		return $this;
	}

	public function xml() {
		$this->append( $this->dom->createProcessingInstruction( 'xml', 'version="1.0" encoding="UTF-8"' ) );
		return $this;
	}

	public function raw(  $raw_xml ) {
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
				if ( !$this->current_element->parentNode ) {
					throw new OxymelException( 'end has been used without a matching contains' );
				}
				$this->current_element = $this->current_element->parentNode;
				$this->go_up_next_call--;
			}
			$this->go_up_next_call = 0;
		}
		$this->latest_inserted = $this->current_element->appendChild($element);
	}

	private function get_content_and_attributes_from_tag_args( $content_or_attributes, $attributes ) {
		$content = null;
		if ( !$attributes ) {
			if ( is_array( $content_or_attributes ) )
				$attributes = $content_or_attributes;
			else
				$content = $content_or_attributes;
		} else {
			$content = $content_or_attributes;
		}
		return array( $content, $attributes );
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
}

class OxymelException extends Exception {
}
