<?php
class Coupon
{
    protected $ci;

    public function __construct($ci)
    {
        $this->ci = $ci;
    }

    public function updateUseYn($cp_hist_seq, $use_ny)
    {
        try {
            
            $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.arf_basis_coupon_history', [
                'cp_use_ny' => $use_ny
            ], 
            [
                'cp_hist_seq' => $cp_hist_seq
            ]);

            // 여기선 값을 들고있지 않다.
            // $this->ci->relay->couponReflect($product_cd, $product_seq, $result_code, $result_msg, $pay_price, $appl_date, $appl_time, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);

            return "OK";

        } catch (Exception $e) {
            return "Fail";
        }
    }

    public function updateCouponUsed($cp_hist_seq, $use_ny, $cp_use_datetime, $product_seq)
    {
        try {
            
            $this->ci->dbutil->update('iparkingCloudDb', 'fdk_parkingcloud.arf_basis_coupon_history', [
                'cp_use_ny' => $use_ny,
                'cp_hist_mod_datetime' => $cp_use_datetime,
                'cp_ppsl_seq' => $product_seq
            ], 
            [
                'cp_hist_seq' => $cp_hist_seq
            ]);

            // 여기선 값을 들고있지 않다.
            // $this->ci->relay->couponReflect($product_cd, $product_seq, $result_code, $result_msg, $pay_price, $appl_date, $appl_time, $memb_seq, $bcar_seq, $bcar_number, $park_seq, $prdt_seq, $cp_hist_seq, $version);

            return "OK";

        } catch (Exception $e) {
            return "Fail";
        }
    }
}