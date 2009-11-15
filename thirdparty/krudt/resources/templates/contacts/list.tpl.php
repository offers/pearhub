<h2>Contacts</h2>

<?php print $this->collection($contacts)->sort_columns()->row_actions()->paginate()->rowlink(); ?>

