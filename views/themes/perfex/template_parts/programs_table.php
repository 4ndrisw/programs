<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-programs" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th><?php echo _l('program_number'); ?> #</th>
            <th><?php echo _l('program_list_project'); ?></th>
            <th><?php echo _l('program_list_date'); ?></th>
            <th><?php echo _l('program_list_state'); ?></th>

        </tr>
    </thead>
    <tbody>
        <?php foreach($programs as $program){ ?>
            <tr>
                <td><?php echo '<a href="' . site_url("programs/show/" . $program["id"] . '/' . $program["hash"]) . '">' . format_program_number($program["id"]) . '</a>'; ?></td>
                <td><?php echo $program['name']; ?></td>
                <td><?php echo _d($program['date']); ?></td>
                <td><?php echo format_program_state($program['state']); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
