<?php
class FDF 
{
    // FDF File section elements and delimiters
    const FDF_HEADER          = "%FDF-1.2\n%\xe2\xe3\xcf\xd3\r\n1 0 obj\n<<\n/FDF\n";
    const FDF_FIELDS_OPEN     = "<<\n/Fields [\n";
    const FDF_FIELDS_CLOSE    = "]\n";
    const FDF_END_OBJ         = ">>\n>>\nendobj\n";
    const FDF_TRAILER         = "trailer\n\n<<\n/Root 1 0 R\n>>\n";
    const FDF_EOF             = "%%EOF\n\x0a";

    // Templates to create entries withn the FDF file.
    const FDF_TEMPLATE_STRING = "<<\n/V (%s)\n/T (%s)\n%s\n%s\n>>\n";
    const FDF_TEMPLATE_DATA   = "<<\n/V/%s\n/T (%s)\n%s\n%s\n>>\n";
    const FDF_TEMPLATE_URL    = "/F (%s)\n";

    // FDF Specific field values
    const FDF_HIDDEN_SET      = '/SetF 2';
    const FDF_HIDDEN_CLEAR    = '/ClrF 2';
    const FDF_READONLY_SET    = '/SetFf 1';
    const FDF_READONLY_CLEAR  = '/ClrFf 1';
    const FDF_BOOL_TRUE       = 'Yes';
    const FDF_BOOL_FALSE      = 'No';

    private $data;
    private $readonlyFields;
    private $hiddenFields;

    /**
     * Create a FDF object from an array
     */
    function __construct(array $data, array $readonly=array(), $hidden=array(), 
                         $url=null) {
        $this->data= $data;
        $this->readonlyFields= $readonly;
        $this->hiddenFields= $hidden;
    }

    /**
     * Obtain the actual contents of the FDF file.
     */
    private function getContents() {
        $fdf= '';
        $fdf .= self::FDF_HEADER;
        $fdf .= self::FDF_FIELDS_OPEN;
        foreach($this->data as $name => $value)   
            if (gettype($value) == 'string')
                $fdf .= self::toFDFStringField($name, $value, 
                                               in_array($name, $this->hiddenFields),
                                               in_array($name, $this->readonlyFields));
            else 
                $fdf .= self::toFDFDataField($name, $value, 
                                               in_array($name, $this->hiddenFields),
                                               in_array($name, $this->readonlyFields));
        if (!empty($this->url))
            $fdf .= self::toFDFFormUrl($this->url);

        $fdf .= self::FDF_FIELDS_CLOSE;
        $fdf .= self::FDF_END_OBJ;
        $fdf .= self::FDF_TRAILER;
        $fdf .= self::FDF_EOF;
        return $fdf;
    }


    /**
     * Obtain the contents of the FDF File.
     */
    public function __toString() {
        return $this->getContents();
    }

    /**
     * Encode an FDF String field. 
     *
     * @param name       The name of the field.
     * @param value      The value of the field.
     * @param isHidden   If the field is hidden or not.
     * @param isReadonly If the field is in readonly mode or not.
     * 
     * @return The encoded FDF field as string.
     */
    private static function toFDFStringField($name, $value, 
                                             $isHidden=false, $isReadonly=false) {
        return sprintf(self::FDF_TEMPLATE_STRING, 
            self::smartEncode($value),
            self::smartEncode($name),
            $isHidden ? self::FDF_HIDDEN_SET : self::FDF_HIDDEN_CLEAR,
            $isReadonly ? self::FDF_READONLY_SET : self::FDF_READONLY_CLEAR
        );
    }


    /**
     * Encode an FDF data field.
     *
     * @param name       The name of the field.
     * @param value      The value of the field.
     * @param isHidden   If the field is hidden or not.
     * @param isReadonly If the field is in readonly mode or not.
     * 
     * @return The encoded FDF field as string.
     */
    private static function toFDFDataField($name, $value,
                                           $isHidden=false, $isReadonly=false) {
        $val= $value;
        if (gettype($value) == 'boolean')
            $val = $value ? self::FDF_BOOL_TRUE : self::FDF_BOOL_FALSE;

        return sprintf(self::FDF_TEMPLATE_DATA, 
            $val,
            self::smartEncode($name),
            $isHidden ? self::FDF_HIDDEN_SET : self::FDF_HIDDEN_CLEAR,
            $isReadonly ? self::FDF_READONLY_SET : self::FDF_READONLY_CLEAR
        );
    }

    /**
     * Encode the string in UTF-16 BE, escaping the parens.
     *
     * UCS-2 should now be considered obsolete.
     * It no longer refers to an encoding form in either 10646 or the Unicode Standard.
     * https://www.ietf.org/rfc/rfc2781.txt
     * https://en.wikipedia.org/wiki/UTF-16
     * 
     * @param s The string to be encoded.
     * 
     * @return The encoded string.
     */
    private static function smartEncode($s) {
        $utf16= mb_convert_encoding($s, 'UTF-16BE');
        $safe= $utf16;
        $safe= mb_eregi_replace('\)', '\\)', $safe);
        $safe= mb_eregi_replace('\(', '\\(', $safe);
        return pack("n",0xFEFF) . $safe;
    }
}
