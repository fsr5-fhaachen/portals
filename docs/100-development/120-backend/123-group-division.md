# Group Division

Certain events are split into groups. The division process is automated with algorithms you can find in `app/Helpers`.

## Forms of division

There are multiple forms of division:

### Balanced Division

Balanced division means that every group has a user course ratio that is roughly equal to the total ratio of the event.

### Course Division

Every group only has users with certain courses.

### Slot Assignment

An event can have slots with limited user count. Users need to register for slots. The slots then get assigned. The first users to register get assigned first. If a slot is full, the remaining registrations will get assigned to a queue.

## Parameters

There are different parameters to consider when dividing users into groups:

### Consider Alcohol / Minimum non drinkers

If alcohol is considered, then every group must have either 0 non dinkers, or the configured minimum amount. This is to ensure that noone is the only sober person in the group.

### Maximum amount of groups

This parameter controls how many groups are allowed to be created.

### Maximum group size

This controls how many users a group can have.
