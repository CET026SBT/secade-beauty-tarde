# -*- coding: utf-8 -*-
"""
Extrator LOCAL de texto de PDF com fontes Identity-H (uso unico).
Le ToUnicode CMaps (bfchar/bfrange), expande object streams e descodifica
strings hex de 2 bytes com o CMap da fonte activa (Tf).
Sem dependencias externas. Destino: _dev/mapaMentalMVP/.tmp-pdf/<nome>.txt
"""
import os
import re
import sys
import zlib

AQUI = os.path.dirname(os.path.abspath(__file__))
DEST = os.path.join(AQUI, ".tmp-pdf")
if not os.path.isdir(DEST):
    os.makedirs(DEST)

ESCAPES = {b"n": b"\n", b"r": b"\r", b"t": b"\t", b"b": b"\b",
           b"f": b"\f", b"(": b"(", b")": b")", b"\\": b"\\"}
OBJ_RE = re.compile(rb"(\d+)\s+(\d+)\s+obj\b")


def inflate(raw):
    for ini in (0, 1, 2):
        try:
            return zlib.decompress(raw[ini:])
        except Exception:
            pass
    try:
        return zlib.decompressobj().decompress(raw)
    except Exception:
        return None


def ler_streams(data, ini, fim):
    """(dict, conteudo) de cada 'stream ... endstream' entre ini e fim."""
    out = []
    pos = ini
    while True:
        k = data.find(b"stream", pos)
        if k == -1 or k > fim:
            break
        d = k + 6
        if data[d:d + 2] == b"\r\n":
            d += 2
        elif data[d:d + 1] in (b"\n", b"\r"):
            d += 1
        e = data.find(b"endstream", d)
        if e == -1:
            break
        out.append((data[ini:k], data[d:e]))
        pos = e + 9
    return out


def objetos(data):
    objs = {}
    for m in OBJ_RE.finditer(data):
        num = int(m.group(1))
        fim = data.find(b"endobj", m.end())
        if fim == -1:
            continue
        objs[num] = data[m.start():fim]
    return objs


def expandir_objstm(objs):
    """Objetos comprimidos dentro de /Type /ObjStm."""
    novos = {}
    for _num, corpo in list(objs.items()):
        if b"/ObjStm" not in corpo:
            continue
        for dicionario, conteudo in ler_streams(corpo, 0, len(corpo)):
            inf = inflate(conteudo)
            if not inf:
                continue
            mn = re.search(rb"/N\s+(\d+)", dicionario)
            mf = re.search(rb"/First\s+(\d+)", dicionario)
            if not (mn and mf):
                continue
            n, first = int(mn.group(1)), int(mf.group(1))
            cabeca = inf[:first].split()
            for i in range(n):
                try:
                    onum = int(cabeca[2 * i])
                    off = int(cabeca[2 * i + 1])
                except Exception:
                    break
                fim = first + int(cabeca[2 * i + 3]) if 2 * i + 3 < len(cabeca) else len(inf)
                novos.setdefault(onum, inf[first + off:fim])
    return novos


def _utf16(hexs):
    b = bytes.fromhex(hexs if len(hexs) % 2 == 0 else hexs + "0")
    if len(b) >= 2 and len(b) % 2 == 0:
        return b.decode("utf-16-be", errors="replace")
    return b.decode("latin-1", errors="replace")


def cmap_do_stream(conteudo):
    inf = inflate(conteudo)
    if not inf or b"beginbf" not in inf:
        return None
    txt = inf.decode("latin-1")
    mapa = {}
    for bloco in re.findall(r"beginbfchar(.*?)endbfchar", txt, re.S):
        for src, dst in re.findall(r"<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>", bloco):
            mapa[int(src, 16)] = _utf16(dst)
    for bloco in re.findall(r"beginbfrange(.*?)endbfrange", txt, re.S):
        for ini, fim, dst in re.findall(
                r"<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>", bloco):
            a, b, base = int(ini, 16), int(fim, 16), int(dst, 16)
            for k in range(a, min(b, a + 65535) + 1):
                mapa[k] = chr(base + (k - a))
    return mapa


def recursos_fonte(objs):
    """nome do recurso (/F1) -> numero de objeto da fonte."""
    mapa = {}
    for _num, corpo in objs.items():
        for bloco in re.findall(rb"/Font\s*<<(.*?)>>", corpo, re.S):
            for nome, ref in re.findall(rb"/([A-Za-z0-9#+.\-]+)\s+(\d+)\s+0\s+R", bloco):
                mapa[nome.decode("latin-1")] = int(ref)
    return mapa


def fontes_para_cmap(objs):
    """numero da fonte -> CMap ToUnicode."""
    saida = {}
    for num, corpo in objs.items():
        if b"/Font" not in corpo:
            continue
        m = re.search(rb"/ToUnicode\s+(\d+)\s+0\s+R", corpo)
        if not m:
            continue
        alvo = int(m.group(1))
        if alvo not in objs:
            continue
        for _d, conteudo in ler_streams(objs[alvo], 0, len(objs[alvo])):
            cm = cmap_do_stream(conteudo)
            if cm:
                saida[num] = cm
                break
    return saida


def ler_literal(content, i):
    i += 1
    depth, buf, n = 1, bytearray(), len(content)
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
            i += 2
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


def descodificar(bruto, cmap, dois_bytes):
    if cmap and dois_bytes:
        saida = []
        for k in range(0, len(bruto) - 1, 2):
            codigo = (bruto[k] << 8) | bruto[k + 1]
            saida.append(cmap.get(codigo, ""))
        return "".join(saida)
    if cmap and not dois_bytes:
        return "".join(cmap.get(b, chr(b)) for b in bruto)
    return bruto.decode("cp1252", errors="replace")


def texto_do_stream(content, recursos, cmaps):
    partes = []
    fonte = None
    dois = True
    i, n = 0, len(content)
    while i < n:
        ch = content[i:i + 1]
        if ch == b"(":
            s, i = ler_literal(content, i)
            partes.append(descodificar(s, cmaps.get(fonte), dois))
            continue
        if ch == b"<" and content[i + 1:i + 2] != b"<":
            j = content.find(b">", i)
            if j == -1:
                break
            hexs = re.sub(rb"[^0-9A-Fa-f]", b"", content[i + 1:j])
            if len(hexs) % 2:
                hexs += b"0"
            try:
                partes.append(descodificar(bytes.fromhex(hexs.decode()), cmaps.get(fonte), dois))
            except Exception:
                pass
            i = j + 1
            continue
        m = re.match(rb"/([A-Za-z0-9#+.\-]+)\s+[\d.]+\s+Tf", content[i:])
        if m:
            nome = m.group(1).decode("latin-1")
            if recursos.get(nome) in cmaps:
                fonte = recursos[nome]
            i += m.end()
            continue
        m = re.match(rb"(Td|TD|Tm|T\*|ET|BT)", content[i:])
        if m:
            partes.append("\n")
            i += m.end()
            continue
        i += 1
    return "".join(partes)


def main():
    alvos = sorted(f for f in os.listdir(AQUI) if f.lower().endswith(".pdf"))
    if len(sys.argv) > 1:
        alvos = [a for a in alvos if sys.argv[1].lower() in a.lower()]
    if not alvos:
        print("ERRO: sem PDF em " + AQUI)
        return

    for nome in alvos:
        with open(os.path.join(AQUI, nome), "rb") as fh:
            data = fh.read()
        objs = objetos(data)
        objs.update(expandir_objstm(objs))
        recursos = recursos_fonte(objs)
        cmaps = fontes_para_cmap(objs)

        partes = []
        for _num, corpo in objs.items():
            if b"stream" not in corpo:
                continue
            for _d, conteudo in ler_streams(corpo, 0, len(corpo)):
                inf = inflate(conteudo)
                if not inf or (b"Tj" not in inf and b"TJ" not in inf):
                    continue
                partes.append(texto_do_stream(inf, recursos, cmaps))

        linha = []
        for ln in "\n".join(partes).split("\n"):
            ln = re.sub(r"[ \t]+", " ", ln).strip()
            if ln:
                linha.append(ln)
        with open(os.path.join(DEST, nome[:-4] + ".txt"), "w", encoding="utf-8") as fh:
            fh.write("\n".join(linha))
        print("%-38s cmaps=%d recursos=%d -> %4d linhas"
              % (nome, len(cmaps), len(recursos), len(linha)))


if __name__ == "__main__":
    main()