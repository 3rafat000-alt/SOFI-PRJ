# _scratch/ — ephemeral temp scripts (GOVERNANCE Rule 3)
One-off scripts for a single task live here, named `tmp_<role>_<purpose>.py`.
They are PURGED at gate exit (`sofi scratch <PRJ-ID> clean`) and are NEVER a
deliverable. Nothing in docs/ or src/ may import from here. Proved-useful scripts
get promoted to sofi/tooling/ (see sofi/tooling/GOVERNANCE.md).
