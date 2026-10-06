# -*- coding: utf-8 -*-
"""
Extrator LOCAL de texto de PDF (uso unico, ficheiros temporarios em _dev/).
Sem dependencias: zlib + leitura dos content streams (Tj / TJ / ' / " e Td/TD/Tm/T*).
Destino: _dev/mapaMentalMVP/.tmp-pdf/<nome>.txt
"""
import os
import re
import sys
import zlib

AQUI = os.path.dirname(os.path.abspath(__file__))
DEST = os.path.join(AQUI, ".tmp-pdf")

if not os.path.isdir(DEST):
    os.makedirs(DEST)

ESCAPES = {
    b"n": b"\n", b"r": b"\r", b"t": b"\t", b"b": b"\b",
    b"f": b"\f", b"(": b"(", b")": b")", b"\\": b"\\",
}


def inflate(raw):
    """Tenta descomprimir um stream (FlateDecode) tolerando offsets."""
    for ini in (0, 1, 2):
        try:
            return zlib.decompress(raw[ini:])
        except Exception:
            pass
    try:
        return zlib.decompressobj().decompress(raw)
    except Exception:
        return None


def streams(data):
    for m in re.finditer(rb"stream\r?\n", data):
        fim = data.find(b"endstream", m.end())
        if fim == -1:
            continue
        yield data[m.end():fim]


def ler_literal(content, i):
    """Le uma string literal (...) com escapes e parenteses aninhados."""
    i += 1
    depth = 1
    buf = bytearray()
    n = len(content)
    while i < n and depth:
        ch = content[i:i + 1]
        if ch == b"\\":
            nxt = content[i + 1:i + 2]
            if nxt in ESCAPES:
                buf += ESCAPES[nxt]
                i += 2
                continue
            m = re.match(rb"[0-7]{1,3}", content[i + 1:i + 4])
            if m:
                buf.append(int(m.group(0), 8) & 0xFF)
                i += 1 + len(m.group(0))
                continue
            i += 2  # continuacao de linha
            continue
        if ch == b"(":
            depth += 1
            buf += ch
            i += 1
            continue
        if ch == b")":
            depth -= 1
            if depth == 0:
                i += 1
                break
            buf += ch
            i += 1
            continue
        buf += ch
        i += 1
    return bytes(buf), i


def texto_do_stream(content):
    """Extrai texto: strings + quebras em Td/TD/Tm/T*/ET."""
    out = []
    i = 0
    n = len(content)
    while i < n:
        ch = content[i:i + 1]
        if ch == b"(":
            s, i = ler_literal(content, i)
            out.append(s)
            continue
        if ch == b"<" and content[i + 1:i + 2] != b"<":
            j = content.find(b">", i)
            if j == -1:
                break
            hexs = re.sub(rb"[^0-9A-Fa-f]", b"", content[i + 1:j])
            if len(hexs) % 2:
                hexs += b"0"
            try:
                out.append(bytes.fromhex(hexs.decode("ascii")))
            except Exception:
                pass
            i = j + 1
            continue
        m = re.match(rb"(Td|TD|Tm|T\*|ET|BT|Tj|TJ|'|\")", content[i:])
        if m:
            op = m.group(1)
            if op in (b"Td", b"TD", b"Tm", b"T*", b"ET", b"BT"):
                out.append(b"\n")
            i += m.end()
            continue
        i += 1

    bruto = b"".join(out)
    # marca strings de 2 bytes (Identity-H) para diagnostico
    opa = bruto.replace(b"\n", b"\x01")
    txt = opa.decode("cp1252", errors="replace")
    return txt.replace("\x01", "\n")


def pode_ser_hex16(content):
    """Heuristica: o stream usa strings hex de 2 bytes (Identity-H)?."""
    hexs = re.findall(rb"<([0-9A-Fa-f\s]{8,})>", content)
    if not hexs:
        return False
    return all(len(re.sub(rb"[^0-9A-Fa-f]", b"", h)) % 4 == 0 for h in hexs[:20])


def main():
    alvos = [f for f in os.listdir(AQUI) if f.lower().endswith(".pdf")]
    if len(sys.argv) > 1:
        alvos = [a for a in alvos if sys.argv[1].lower() in a.lower()]
    alvos.sort()
    if not alvos:
        print("ERRO: nao encontrei PDF em " + AQUI)
        return

    for nome in alvos:
        with open(os.path.join(AQUI, nome), "rb") as fh:
            data = fh.read()
        partes = []
        hex16 = False
        for raw in streams(data):
            inf = inflate(raw)
            if not inf:
                continue
            if b"Tj" not in inf and b"TJ" not in inf:
                continue
            if pode_ser_hex16(inf):
                hex16 = True
            partes.append(texto_do_stream(inf))

        txt = "\n".join(partes)
        txt = re.sub(r"[ \t]+", " ", txt)
        txt = re.sub(r"\n\s*\n+", "\n", txt)
        linha = [ln.strip() for ln in txt.split("\n")]
        linha = [ln for ln in linha if ln]
        saida = "\n".join(linha)

        dst = os.path.join(DEST, nome[:-4] + ".txt")
        with open(dst, "w", encoding="utf-8") as fh:
            fh.write(saida)
        print("%-38s %5d bytes -> %4d linhas%s"
              % (nome, len(data), len(linha),
                 "   [AVISO: strings hex de 2 bytes]" if hex16 else ""))


if __name__ == "__main__":
    main()