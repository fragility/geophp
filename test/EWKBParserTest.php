<?php
require_once 'PHPUnit/Extensions/ExceptionTestCase.php';
require_once dirname(__FILE__).'/../lib/GeoPHP.php';

class EWKBParserTest extends PHPUnit_Extensions_ExceptionTestCase
{
	protected $parser;
	
	protected function setUp()
	{
		$this->parser = new HexEWKBParser;
	}
	
	public function test_fail_truncated_data()
	{
		$this->setExpectedException('EWKBFormatError');
		$point = $this->parser->parse('0101000020BC01000000000000000008');
	}
	
	public function test_fail_extra_data()
	{
		$this->setExpectedException('EWKBFormatError');
		// Added F00 to the end
		$point = $this->parser->parse('0101000020BC01000000000000000008400000000000001440F00');
	}
	
	public function test_fail_bad_geometry_type()
	{
		$this->setExpectedException('EWKBFormatError');
		// Bad geometry type 9
		$point = $this->parser->parse('0109000020BC01000000000000000008400000000000001440');
	}
	
	public function test_fail_no_m()
	{
		$this->setExpectedException('EWKBFormatError');
		// Turned on with_m flag, but no m coordinate
		$point = $this->parser->parse('0101000060BC01000000000000000008400000000000001440');
	}

	public function test_point2()
	{
		$point = $this->parser->parse('0101000020BC01000000000000000008400000000000001440');
		$this->assertEquals('GeoPHP_Point', get_class($point));
		$this->assertEquals(444, $point->srid);
		$this->assertEquals(3, $point->x);
		$this->assertEquals(5, $point->y);
		$this->assertEquals(false, $point->with_z);
		$this->assertEquals(false, $point->with_m);
	}
	
	public function test_point3z()
	{
		$point = $this->parser->parse('01010000A0BC01000000000000000008400000000000001440A245B6F3FD541DC0');
		$this->assertEquals('GeoPHP_Point', get_class($point));
		$this->assertEquals(444, $point->srid);
		$this->assertEquals(3, $point->x);
		$this->assertEquals(5, $point->y);
		$this->assertEquals(-7.333, $point->z);
	}
	
	public function test_point3m()
	{
		$point = $this->parser->parse('0101000060BC01000000000000000008400000000000001440A245B6F3FD541DC0');
		$this->assertEquals('GeoPHP_Point', get_class($point));
		$this->assertEquals(444, $point->srid);
		$this->assertEquals(3, $point->x);
		$this->assertEquals(5, $point->y);
		$this->assertEquals(-7.333, $point->m);
	}
	
	public function test_point4()
	{
		$point = $this->parser->parse('01010000E0BC01000000000000000008400000000000001440A245B6F3FD541DC0C93EC8B2606B5A40');
		$this->assertEquals('GeoPHP_Point', get_class($point));
		$this->assertEquals(444, $point->srid);
		$this->assertEquals(3, $point->x);
		$this->assertEquals(5, $point->y);
		$this->assertEquals(-7.333, $point->z);
		$this->assertEquals(105.677777, $point->m);
	}
	
	public function test_point_no_srid()
	{
		$point = $this->parser->parse('010100000000000000000008400000000000001440');
		$this->assertEquals('GeoPHP_Point', get_class($point));
		$this->assertEquals(GeoPHP::DEFAULT_SRID, $point->srid);
		$this->assertEquals(3, $point->x);
		$this->assertEquals(5, $point->y);
	}
	
	
	/*
	public function test_point_bigendian()
	{
		// From GeoRuby test suite
		$point = $this->parser->parse('00000000014013A035BD512EC7404A3060C38F3669');
		$this->assertTrue($point instanceof GeoPHP_Point);
		$this->assertEquals(4.906455, $point->x);
		$this->assertEquals(52.377953, $point->y);
	}
	*/
	
	public function test_linestring()
	{
		$coords = array(array(3, 5, 1.04, 4), array(-5.55, 3.14, 25.5, 5));
		
		// 2d
		$line = $this->parser->parse('0102000000020000000000000000000840000000000000144033333333333316C01F85EB51B81E0940');
		$this->assertEquals('GeoPHP_LineString', get_class($line));
		$this->assertEquals(GeoPHP::DEFAULT_SRID, $line->srid);
		$this->assertEquals(2, count($line->points));
		$this->assertEquals(GeoPHP_LineString::from_array($coords), $line);

		// 3dz
		$line = $this->parser->parse('01020000A0BC0100000200000000000000000008400000000000001440A4703D0AD7A3F03F33333333333316C01F85EB51B81E09400000000000803940');
		$this->assertEquals('GeoPHP_LineString', get_class($line));
		$this->assertEquals(GeoPHP_LineString::from_array($coords, 444, true), $line);

		// 3dm
		$line = $this->parser->parse('0102000060BC0100000200000000000000000008400000000000001440A4703D0AD7A3F03F33333333333316C01F85EB51B81E09400000000000803940');
		$this->assertEquals('GeoPHP_LineString', get_class($line));
		$this->assertEquals(GeoPHP_LineString::from_array($coords, 444, false, true), $line);

		// 4d
		$line = $this->parser->parse('01020000E0BC0100000200000000000000000008400000000000001440A4703D0AD7A3F03F000000000000104033333333333316C01F85EB51B81E094000000000008039400000000000001440');
		$this->assertEquals('GeoPHP_LineString', get_class($line));
		$this->assertEquals(GeoPHP_LineString::from_array($coords, 444, true, true), $line);
	}
	
	public function test_polygon()
	{
		$ring1_coords = array(array(0,0,0,4),
		                array(0,5,1,3),
		                array(5,5,2,2),
		                array(5,0,1,1),
		                array(0,0,0,4));
		$ring2_coords = array(array(1,1,0,-2),
		                array(1,4,1,-3),
		                array(4,4,2,-4),
		                array(4,1,1,-5),
		                array(1,1,0,-2));
		                
		// 2d
		$poly = $this->parser->parse('0103000020BC0100000200000005000000000000000000000000000000000000000000000000000000000000000000144000000000000014400000000000001440000000000000144000000000000000000000000000000000000000000000000005000000000000000000F03F000000000000F03F000000000000F03F0000000000001040000000000000104000000000000010400000000000001040000000000000F03F000000000000F03F000000000000F03F');
		$this->assertEquals('GeoPHP_Polygon', get_class($poly));
		$this->assertEquals(GeoPHP_Polygon::from_array(array($ring1_coords, $ring2_coords), 444), $poly);
		                
		// 3dz
		$poly = $this->parser->parse('01030000A0BC010000020000000500000000000000000000000000000000000000000000000000000000000000000000000000000000001440000000000000F03F00000000000014400000000000001440000000000000004000000000000014400000000000000000000000000000F03F00000000000000000000000000000000000000000000000005000000000000000000F03F000000000000F03F0000000000000000000000000000F03F0000000000001040000000000000F03F0000000000001040000000000000104000000000000000400000000000001040000000000000F03F000000000000F03F000000000000F03F000000000000F03F0000000000000000');
		$this->assertEquals('GeoPHP_Polygon', get_class($poly));
		$this->assertEquals(GeoPHP_Polygon::from_array(array($ring1_coords, $ring2_coords), 444, true), $poly);
		                
		// 3dm
		$poly = $this->parser->parse('0103000060BC010000020000000500000000000000000000000000000000000000000000000000000000000000000000000000000000001440000000000000F03F00000000000014400000000000001440000000000000004000000000000014400000000000000000000000000000F03F00000000000000000000000000000000000000000000000005000000000000000000F03F000000000000F03F0000000000000000000000000000F03F0000000000001040000000000000F03F0000000000001040000000000000104000000000000000400000000000001040000000000000F03F000000000000F03F000000000000F03F000000000000F03F0000000000000000');
		$this->assertEquals('GeoPHP_Polygon', get_class($poly));
		$this->assertEquals(GeoPHP_Polygon::from_array(array($ring1_coords, $ring2_coords), 444, false, true), $poly);
		                
		// 4d
		$poly = $this->parser->parse('01030000E0BC0100000200000005000000000000000000000000000000000000000000000000000000000000000000104000000000000000000000000000001440000000000000F03F0000000000000840000000000000144000000000000014400000000000000040000000000000004000000000000014400000000000000000000000000000F03F000000000000F03F000000000000000000000000000000000000000000000000000000000000104005000000000000000000F03F000000000000F03F000000000000000000000000000000C0000000000000F03F0000000000001040000000000000F03F00000000000008C000000000000010400000000000001040000000000000004000000000000010C00000000000001040000000000000F03F000000000000F03F00000000000014C0000000000000F03F000000000000F03F000000000000000000000000000000C0');
		$this->assertEquals('GeoPHP_Polygon', get_class($poly));
		$this->assertEquals(GeoPHP_Polygon::from_array(array($ring1_coords, $ring2_coords), 444, true, true), $poly);
	}
	
	public function test_geometrycollection()
	{
		
	}
	
	public function test_multipoint()
	{
		
	}
	
	public function test_multilinestring()
	{
		
	}
	
	public function test_multipolygon()
	{
		
	}
}
?>
