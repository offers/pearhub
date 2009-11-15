<h2>Projects</h2>

<?php print $this->collection($projects)->sort_columns()->row_actions()->paginate()->rowlink(); ?>

