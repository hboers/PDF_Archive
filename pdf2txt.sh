# SPDX-License-Identifier: MIT
find . -name *.pdf |  sed -e "s/^/pdftotext \"/" -e "s/$/\"/"  | sh
