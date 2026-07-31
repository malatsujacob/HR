<?php
// module7_payroll/disbursement_engine.php
?>
<div style="background: #ffffff; padding: 20px; border-radius: 6px; border: 1px solid #b3d1ff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px;">
    <h3 style="margin-top: 0; font-size: 14px; color: #1e40af; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
        <span style="color: #dc2626;">CHAP CHAP</span> <span style="color: #2563eb;">UGX</span> Disbursement Engine
    </h3>
    
    <form method="POST" action="execute_payroll.php">
        <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">Payroll Period (Month)</label>
            <input type="text" name="payroll_period" value="<?php echo date('F Y'); ?>" style="padding: 8px 10px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px; width: 100%; box-sizing: border-box; background: #ffffff; color: #0f172a;">
        </div>
        
        <button type="submit" name="execute_disbursement" style="background: #2563eb; color: #ffffff; padding: 8px 14px; border-radius: 4px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px;">
            Execute Payroll & UGX Disbursement
        </button>
    </form>
</div>