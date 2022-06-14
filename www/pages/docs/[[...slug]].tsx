import { getMdxNode, getMdxPaths } from "next-mdx/server"
import { useHydrate } from "next-mdx/client"
import { getTableOfContents, TableOfContents } from "next-mdx-toc"

import { Doc } from "types"
import { docsConfig } from "config/docs"
import { Layout } from "components/layout"
import { SidebarNav } from "components/sidebar-nav"
import { Pager } from "components/pager"
import { Toc } from "components/toc"
import { mdxComponents } from "components/mdx"

export interface DocsPageProps {
  doc: Doc
  toc: TableOfContents
}

export default function DocsPage({ doc, toc }: DocsPageProps) {
  const content = useHydrate(doc, {
    components: mdxComponents,
  })

  return (
    <Layout title={doc.frontMatter.title} description={doc.frontMatter.excerpt}>
      <div className="container px-6 mx-auto md:gap-10 xl:gap-8 md:grid-cols-6 lg:grid-cols-6 xl:grid-cols-[260px_1fr] lg:px-4 lg:grid xl:px-6">
        <aside className="sticky top-0 hidden max-h-screen col-span-2 pt-10 pb-64 pl-2 pr-4 overflow-y-auto border-r xl:col-span-1 lg:flex">
          <SidebarNav items={docsConfig.links} />
        </aside>
        <div className="items-start col-span-4 gap-12 pb-10 xl:col-span-1 xl:grid xl:grid-cols-3 xl:gap-18">
          <div className="col-span-2 pt-4 sm:pt-6 md:pt-10 DocSearch-content main-content">
            <h1 className="text-3xl font-bold leading-tight sm:text-4xl">
              {doc.frontMatter.title}
            </h1>
            {doc.frontMatter.excerpt ? (
              <p className="mt-2 text-lg font-light text-gray-700 md:text-xl">
                {doc.frontMatter.excerpt}
              </p>
            ) : null}
            <hr className="my-6" />
            {content}
            <Pager links={docsConfig.links} />
          </div>
          <aside className="sticky top-0 hidden pt-10 xl:block">
            {toc.items?.length && (
              <div className="p-4 border rounded-md">
                <h2 className="mb-1 text-sm font-medium rounded-md">
                  On this page
                </h2>
                <Toc tree={toc} />
              </div>
            )}
          </aside>
        </div>
      </div>
    </Layout>
  )
}

export async function getStaticPaths() {
  return {
    paths: await getMdxPaths("doc"),
    fallback: false,
  }
}

export async function getStaticProps(context) {
  const doc = await getMdxNode("doc", context, {
    components: mdxComponents,
    mdxOptions: {
      remarkPlugins: [
        require("remark-slug"),
        require("remark-autolink-headings"),
      ],
    },
  })

  if (!doc) {
    return {
      notFound: true,
    }
  }

  return {
    props: {
      doc,
      toc: await getTableOfContents(doc),
    },
  }
}
